<script lang="ts">
    import { Form, Link } from '@inertiajs/svelte';
    import AppHead from '@/components/AppHead.svelte';
    import InputError from '@/components/InputError.svelte';
    import { Button, Input } from 'daisy-svelte';
    import { toUrl } from '@/lib/utils';
    import { login } from '@/routes';
    import { store as storeBeta } from '@/routes/beta';

    let {
        prefill = { name: '', email: '' },
    }: { prefill?: { name: string; email: string } } = $props();
</script>

<AppHead title="Request beta access" />

<div
    data-theme="stdout-dark"
    class="house flex min-h-screen flex-col text-foreground antialiased"
>
    <header
        class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4"
    >
        <Link
            href={toUrl('/')}
            class="font-display text-xl font-semibold tracking-tight select-none"
        >
            std-out<span class="text-primary">.</span>
        </Link>
        <Link
            href={toUrl(login())}
            class="rounded-md border border-border px-4 py-1.5 text-sm text-foreground/90 transition-colors hover:border-primary/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            Log in
        </Link>
    </header>

    <main class="flex grow flex-col items-center justify-center px-6 py-10">
        <div class="w-full max-w-md">
            <div class="mb-6 text-center">
                <h1 class="font-display text-3xl font-bold tracking-tight">
                    Request beta access
                </h1>
                <p class="mt-2 text-sm text-foreground/75">
                    std-out is in private beta. Leave your details and we'll let
                    you in as spots open up.
                </p>
            </div>

            <div class="rounded-xl border border-border bg-card p-6 shadow-xl">
                <Form {...storeBeta.form()} class="grid gap-4" resetOnSuccess>
                    {#snippet children({ errors, processing })}
                        <Input
                            label="Name"
                            id="name"
                            name="name"
                            type="text"
                            placeholder="Ada Lovelace"
                            value={prefill.name}
                            required
                            error={errors.name}
                            data-test="beta-name"
                            class="text-accent-foreground"
                        />

                        <Input
                            label="Email address"
                            id="email"
                            name="email"
                            type="email"
                            placeholder="you@example.com"
                            value={prefill.email}
                            required
                            error={errors.email}
                            data-test="beta-email"
                            class="text-accent-foreground"
                        />

                        <div class="grid gap-2">
                            <label class="label" for="message">
                                What would you use it for?
                                <span class="text-muted-foreground"
                                    >(optional)</span
                                >
                            </label>
                            <textarea
                                id="message"
                                name="message"
                                rows="4"
                                placeholder="Tell us a little about your talks."
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                data-test="beta-message"
                            ></textarea>
                            <InputError message={errors.message} />
                        </div>

                        <Button
                            type="submit"
                            disabled={processing}
                            class="mt-2 bg-primary font-medium text-primary-foreground hover:bg-primary/90"
                            data-test="beta-submit"
                        >
                            {processing ? 'Sending…' : 'Request access'}
                        </Button>
                    {/snippet}
                </Form>
            </div>
        </div>
    </main>
</div>

<style>
    .house {
        background-color: hsl(240 3% 6%);
        background-image: radial-gradient(
            120% 60% at 50% -10%,
            hsl(135 50% 25% / 0.14),
            transparent 65%
        );
    }
</style>
