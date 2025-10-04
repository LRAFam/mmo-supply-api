<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="s3ImageUploadComponent({
            state: $wire.$entangle('{{ $getStatePath() }}'),
            uploadEndpoint: '{{ $getUploadEndpoint() }}'
        })" class="space-y-2">

        <input
            type="file"
            x-ref="fileInput"
            @change="uploadFile"
            accept="image/*"
            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
        />

        <div x-show="uploading" class="text-sm text-gray-600">
            Uploading...
        </div>

        <div x-show="error" class="text-sm text-red-600" x-text="error"></div>

        <div x-show="imageUrl" class="mt-2">
            <img :src="imageUrl" alt="Preview" class="max-w-xs rounded border">
            <p class="text-xs text-gray-500 mt-1">Path: <span x-text="state"></span></p>
        </div>
    </div>
</x-dynamic-component>

<script>
function s3ImageUploadComponent({ state, uploadEndpoint }) {
    return {
        state: state,
        uploading: false,
        error: null,
        imageUrl: null,

        async uploadFile(event) {
            const file = event.target.files[0];
            if (!file) return;

            this.uploading = true;
            this.error = null;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch(uploadEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.state = data.path;
                    this.imageUrl = data.url;
                } else {
                    this.error = data.error || 'Upload failed';
                }
            } catch (err) {
                this.error = 'Upload error: ' + err.message;
            } finally {
                this.uploading = false;
            }
        }
    }
}
</script>
