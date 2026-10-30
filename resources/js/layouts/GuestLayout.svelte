<script lang="ts">
    import type { Snippet } from 'svelte';

    let { children }: { children?: Snippet } = $props();
</script>

<!--
    Guest shell for audience-facing pages. Always the darkened house,
    regardless of the app's light/dark theme: a near-black neutral backdrop
    with a faint phosphor glow from the stage above.
-->
<div
    data-theme="stdout-dark"
    class="guest-house relative flex min-h-dvh flex-col overflow-hidden text-foreground antialiased"
>
    {@render children?.()}
</div>

<style>
    .guest-house {
        background-color: hsl(240 3% 6%);
        background-image: radial-gradient(
            120% 60% at 50% -10%,
            hsl(135 50% 25% / 0.14),
            transparent 65%
        );
    }
</style>
