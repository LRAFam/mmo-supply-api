<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;

class SetupAIConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:setup-conversations {--dry-run : Run in dry-run mode without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create AI Agent conversations for all existing users who don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode - no changes will be made');
            $this->newLine();
        }

        // Find AI Agent
        $aiAgent = User::where('role', 'system')
            ->where('email', 'agent@mmosupply.com')
            ->first();

        if (!$aiAgent) {
            $this->error('AI Agent user not found! Please run php artisan db:seed --class=AIAgentSeeder first.');
            return 1;
        }

        $this->info("Found AI Agent: {$aiAgent->name} (ID: {$aiAgent->id})");
        $this->newLine();

        // Find all users without AI Agent conversation
        // Get users who have conversations with AI
        $usersWithAI = Conversation::where(function ($query) use ($aiAgent) {
            $query->where('user_one_id', $aiAgent->id)
                ->orWhere('user_two_id', $aiAgent->id);
        })
        ->get()
        ->map(function ($conversation) use ($aiAgent) {
            return $conversation->user_one_id === $aiAgent->id
                ? $conversation->user_two_id
                : $conversation->user_one_id;
        })
        ->unique()
        ->toArray();

        // Get all regular users without AI conversation
        $usersWithoutAI = User::where('role', '!=', 'system')
            ->whereNotIn('id', $usersWithAI)
            ->get();

        $totalUsers = $usersWithoutAI->count();

        if ($totalUsers === 0) {
            $this->info('All users already have AI Agent conversations. Nothing to do!');
            return 0;
        }

        $this->info("Found {$totalUsers} users without AI Agent conversations");
        $this->newLine();

        if ($dryRun) {
            $this->table(
                ['ID', 'Name', 'Email', 'Role'],
                $usersWithoutAI->take(10)->map(fn($u) => [$u->id, $u->name, $u->email, $u->role])
            );

            if ($totalUsers > 10) {
                $this->info("... and " . ($totalUsers - 10) . " more users");
            }

            $this->newLine();
            $this->info('Run without --dry-run to create conversations');
            return 0;
        }

        // Confirm before proceeding
        if (!$this->confirm("Do you want to create AI Agent conversations for {$totalUsers} users?", true)) {
            $this->info('Cancelled.');
            return 0;
        }

        // Create conversations with progress bar
        $progressBar = $this->output->createProgressBar($totalUsers);
        $progressBar->start();

        $created = 0;
        $errors = 0;

        foreach ($usersWithoutAI as $user) {
            try {
                // Create conversation
                $conversation = Conversation::create([
                    'user_one_id' => $user->id,
                    'user_two_id' => $aiAgent->id,
                    'subject' => 'Chat with MMO Supply Assistant',
                    'last_message_at' => now(),
                ]);

                // Send welcome message from AI Agent
                Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $aiAgent->id,
                    'type' => 'ai_agent',
                    'message' => "👋 Hi {$user->name}! I'm your MMO Supply Assistant.\n\n" .
                                "I'm here to help you 24/7! I can:\n" .
                                "• Track your orders and listings\n" .
                                "• Check your stats and achievements\n" .
                                "• Answer questions about the platform\n" .
                                "• Provide personalized recommendations\n\n" .
                                "Just ask me anything - try \"What's my wallet balance?\" or \"How many achievement points do I have?\"",
                    'is_read' => false,
                ]);

                $created++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error creating conversation for user {$user->id} ({$user->name}): {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info("✓ Successfully created {$created} AI Agent conversations");

        if ($errors > 0) {
            $this->warn("✗ Failed to create {$errors} conversations (see errors above)");
        }

        return 0;
    }
}
