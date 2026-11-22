<script setup>
defineProps({ label: String, error: String });
</script>
<template>
    <div>
        <label v-if="label" class="label">{{ label }}</label>
        <slot />
        <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>
