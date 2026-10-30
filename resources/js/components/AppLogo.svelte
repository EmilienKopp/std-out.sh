<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import { getContext } from 'svelte';
    import type { SidebarContext } from '@/components/ui/sidebar/context';
    import { SIDEBAR_CONTEXT } from '@/components/ui/sidebar/context';

    const { isMobile, state } = getContext<SidebarContext>(SIDEBAR_CONTEXT);
    const appName = $derived((page.props.name as string) ?? 'std-out');
</script>

<div class="ml-1 grid flex-1 text-left text-sm">
    <span class="font-mono text-xl font-semibold tracking-tight select-none">
        <span class="text-primary">$</span>
        {#if !$isMobile && $state !== 'collapsed'}
            {appName}<span class="text-primary">.</span>
        {/if}
    </span>
</div>
