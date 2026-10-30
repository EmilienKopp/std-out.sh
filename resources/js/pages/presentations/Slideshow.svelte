<script lang="ts">
    import AppHead from '@/components/AppHead.svelte';
    import ExternalPresenter from '@/components/stdout/ExternalPresenter.svelte';
    import Presenter from '@/components/stdout/Presenter.svelte';
    import type {
        FlowGraph,
        PresentationContent,
        PresentationSource,
    } from '@/types/generated';

    // The anonymous guest view: the deck and nothing else — no dock, no
    // reactions, no translation, no sockets. Navigation is the Presenter's
    // own (keyboard / click).
    let {
        presentation,
        sourcePdfUrl = null,
    }: {
        presentation: {
            name: string;
            content: PresentationContent;
            flow: FlowGraph | null;
            source: PresentationSource;
        };
        sourcePdfUrl?: string | null;
    } = $props();
</script>

<AppHead title={presentation.name} />

<div
    class="flex h-screen w-screen items-center justify-center overflow-hidden bg-black [container-type:size]"
>
    <!-- The slide box is the largest 16:9 that fits the viewport on both
         axes, centered, so wide screens letterbox with black around it
         instead of the slide stretching to fill. -->
    <div style="width: min(100cqw, calc(100cqh * 16 / 9)); aspect-ratio: 16 / 9;">
        {#if presentation.source.type === 'editor'}
            <Presenter
                content={presentation.content}
                flow={presentation.flow}
            />
        {:else}
            <ExternalPresenter source={presentation.source} {sourcePdfUrl} />
        {/if}
    </div>
</div>
