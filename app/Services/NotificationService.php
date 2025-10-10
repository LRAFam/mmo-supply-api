<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\Conversation;
use App\Models\Message;

class NotificationService
{
    /**
     * Get the AI Agent system user
     */
    private function getAIAgent(): ?User
    {
        return User::where('email', 'agent@mmosupply.com')->first();
    }

    /**
     * Send notification with AI agent message
     */
    public function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
        ?string $link = null,
        ?array $actions = null
    ): Notification {
        $aiAgent = $this->getAIAgent();

        // Create or get conversation with AI agent
        $conversation = null;
        if ($aiAgent) {
            $conversation = Conversation::findOrCreate($userId, $aiAgent->id);
            $conversation->update([
                'subject' => $title,
                'last_message_at' => now(),
            ]);

            // Send message from AI agent
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $aiAgent->id,
                'type' => 'ai_agent',
                'message' => $message,
                'metadata' => $data,
                'actions' => $actions,
                'is_read' => false,
            ]);
        }

        // Create notification
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'link' => $link,
            'conversation_id' => $conversation?->id,
        ]);
    }

    /**
     * Order status update notification
     */
    public function orderStatusUpdated(int $userId, int $orderId, string $status, string $orderTitle): Notification
    {
        return $this->send(
            userId: $userId,
            type: 'order_update',
            title: "Order #{$orderId} Updated",
            message: "Your order \"{$orderTitle}\" status has been updated to: {$status}",
            data: ['order_id' => $orderId, 'status' => $status],
            link: "/orders/{$orderId}",
            actions: [
                ['label' => 'View Order', 'link' => "/orders/{$orderId}", 'type' => 'primary'],
            ]
        );
    }

    /**
     * Featured listing expiring soon
     */
    public function featuredExpiringSoon(int $userId, int $listingId, string $listingType, string $listingTitle, int $hoursRemaining): Notification
    {
        return $this->send(
            userId: $userId,
            type: 'featured_expiring',
            title: "Featured Listing Expiring Soon",
            message: "Your featured listing \"{$listingTitle}\" will expire in {$hoursRemaining} hours.",
            data: [
                'listing_id' => $listingId,
                'listing_type' => $listingType,
                'hours_remaining' => $hoursRemaining
            ],
            link: "/{$listingType}s/{$listingId}",
            actions: [
                ['label' => 'Extend Featured', 'link' => "/seller/featured/extend/{$listingId}", 'type' => 'primary'],
                ['label' => 'View Listing', 'link' => "/{$listingType}s/{$listingId}", 'type' => 'secondary'],
            ]
        );
    }

    /**
     * New review notification
     */
    public function newReview(int $sellerId, int $productId, string $productType, string $productTitle, int $rating, string $reviewerName): Notification
    {
        return $this->send(
            userId: $sellerId,
            type: 'new_review',
            title: "New {$rating}★ Review",
            message: "{$reviewerName} left a {$rating}-star review on your {$productType} \"{$productTitle}\"",
            data: [
                'product_id' => $productId,
                'product_type' => $productType,
                'rating' => $rating,
            ],
            link: "/{$productType}s/{$productId}",
            actions: [
                ['label' => 'View Review', 'link' => "/{$productType}s/{$productId}#reviews", 'type' => 'primary'],
            ]
        );
    }

    /**
     * New order for seller
     */
    public function newOrderForSeller(int $sellerId, int $orderId, string $productTitle, float $amount): Notification
    {
        return $this->send(
            userId: $sellerId,
            type: 'new_order',
            title: "New Order Received!",
            message: "You have a new order for \"{$productTitle}\" - \${$amount}",
            data: ['order_id' => $orderId, 'amount' => $amount],
            link: "/orders/{$orderId}",
            actions: [
                ['label' => 'View Order', 'link' => "/orders/{$orderId}", 'type' => 'primary'],
                ['label' => 'Mark Delivered', 'action' => 'mark_delivered', 'order_id' => $orderId, 'type' => 'success'],
            ]
        );
    }

    /**
     * Achievement unlocked notification
     */
    public function achievementUnlocked(int $userId, string $achievementName, int $points): Notification
    {
        return $this->send(
            userId: $userId,
            type: 'achievement_unlocked',
            title: "🏆 Achievement Unlocked!",
            message: "You unlocked \"{$achievementName}\" and earned {$points} achievement points!",
            data: ['achievement_name' => $achievementName, 'points' => $points],
            link: "/achievements",
            actions: [
                ['label' => 'View Achievements', 'link' => '/achievements', 'type' => 'primary'],
                ['label' => 'Claim Reward', 'action' => 'claim_achievement', 'achievement_name' => $achievementName, 'type' => 'success'],
            ]
        );
    }

    /**
     * Get unread notifications count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)->unread()->count();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId): void
    {
        $notification = Notification::find($notificationId);
        $notification?->markAsRead();
    }

    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead(int $userId): void
    {
        Notification::forUser($userId)->unread()->update(['read_at' => now()]);
    }
}
