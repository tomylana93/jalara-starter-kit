<script setup lang="ts">
import BrandIdentity from '@/components/BrandIdentity.vue';
</script>

<template>
    <!--
        Every branded surface renders through BrandIdentity so the identity mode
        and the dark/static fallbacks are decided in exactly one place.
    -->
    <BrandIdentity class="flex-1 text-left" />
</template>
