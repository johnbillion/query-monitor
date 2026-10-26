<script setup lang="ts">
import { VPButton } from 'vitepress/theme'
import { chromeExtURL } from '../urls'
</script>

<template>
	<VPButton :href="chromeExtURL" text="Install from the Chrome Web Store" theme="brand" />
</template>
