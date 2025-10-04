<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="s3ImageUploadComponent({
            state: $wire.$entangle('{{ $getStatePath() }}'),
            uploadEndpoint: '{{ $getUploadEndpoint() }}',
            multiple: {{ $isMultiple() ? 'true' : 'false' }}
        })" class="space-y-2">

        <input
            type="file"
            x-ref="fileInput"
            @change="uploadFile"
            accept="image/*"
            :multiple="multiple"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        />

        <div x-show="uploading" class="text-sm text-gray-600">
            Uploading...
        </div>

        <div x-show="error" class="text-sm text-red-600" x-text="error"></div>

        <!-- Single image preview -->
        <div x-show="!multiple && imageUrl" class="mt-2">
            <img :src="imageUrl" alt="Preview" class="max-w-xs rounded border">
            <p class="text-xs text-gray-500 mt-1">Path: <span x-text="state"></span></p>
        </div>

        <!-- Multiple images preview -->
        <template x-if="multiple && images.length > 0">
            <div class="grid grid-cols-3 gap-4 mt-2">
                <template x-for="(img, index) in images" :key="index">
                    <div class="relative">
                        <img :src="img.url" alt="Preview" class="w-full rounded border">
                        <button type="button" @click="removeImage(index)" class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600">
                            ×
                        </button>
                        <p class="text-xs text-gray-500 mt-1 truncate" :title="img.path" x-text="img.path"></p>
                    </div>
                </template>
            </div>
        </template>
    </div>
</x-dynamic-component>

<script>
function s3ImageUploadComponent({ state, uploadEndpoint, multiple }) {
    return {
        state: state,
        uploading: false,
        error: null,
        imageUrl: null,
        images: [],
        multiple: multiple,

        init() {
            // Initialize images array if multiple
            if (this.multiple && Array.isArray(this.state)) {
                this.images = this.state.map(path => ({
                    path: path,
                    url: null // We'll need to fetch URLs separately
                }));
            }
        },

        async uploadFile(event) {
            const files = event.target.files;
            if (!files || files.length === 0) return;

            this.uploading = true;
            this.error = null;

            try {
                if (this.multiple) {
                    // Upload multiple files
                    for (const file of files) {
                        await this.uploadSingleFile(file);
                    }
                } else {
                    // Upload single file
                    await this.uploadSingleFile(files[0]);
                }
            } finally {
                this.uploading = false;
                event.target.value = ''; // Reset input
            }
        },

        async uploadSingleFile(file) {
            const formData = new FormData();
            formData.append('image', file);

            const response = await fetch(uploadEndpoint, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (data.success) {
                if (this.multiple) {
                    // Add to images array
                    this.images.push({
                        path: data.path,
                        url: data.url
                    });
                    // Update state with paths only
                    this.state = this.images.map(img => img.path);
                } else {
                    this.state = data.path;
                    this.imageUrl = data.url;
                }
            } else {
                this.error = data.error || 'Upload failed';
                throw new Error(data.error);
            }
        },

        removeImage(index) {
            this.images.splice(index, 1);
            this.state = this.images.map(img => img.path);
        }
    }
}
</script>
