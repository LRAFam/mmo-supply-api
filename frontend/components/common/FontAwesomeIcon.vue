<template>
    <component :is="tag" :class="computedClass" @click="handleClick">
        <i :class="iconClass"></i>
        <span v-if="text" class="ml-2">{{ text }}</span>
    </component>
</template>

<script>
export default {
    props: {
        iconName: {
            type: String,
            required: true
        },
        variant: {
            type: String,
            default: 'fas'
        },
        size: {
            type: String,
            default: '1x'
        },
        extraClasses: {
            type: String,
            default: ''
        },
        text: {
            type: String,
            default: ''
        },
        tag: {
            type: String,
            default: 'button'
        }
    },
    computed: {
        iconClass() {
            return [this.variant, `fa-${this.iconName}`, `fa-${this.size}`];
        },
        computedClass() {
            return {
                'icon-button': this.tag === 'button',
                [this.extraClasses]: true
            };
        }
    },
    methods: {
        handleClick() {
            this.$emit('click');
        }
    }
}
</script>
