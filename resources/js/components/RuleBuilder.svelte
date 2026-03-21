<script lang="ts">
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import {
        Select,
        SelectContent,
        SelectItem,
        SelectTrigger,
    } from '@/components/ui/select';

    interface EnumOption {
        value: string;
        label: string;
    }

    interface Rule {
        type: string;
        key: string;
        operator: string;
        value: string;
        priority: number;
    }

    let {
        rules = $bindable([]),
        ruleTypes,
        ruleOperators,
        errors = {},
    }: {
        rules: Rule[];
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
        errors: Record<string, string>;
    } = $props();

    function addRule(): void {
        rules = [
            ...rules,
            {
                type: ruleTypes[0]?.value ?? '',
                key: '',
                operator: ruleOperators[0]?.value ?? '',
                value: '',
                priority: rules.length,
            },
        ];
    }

    function removeRule(index: number): void {
        rules = rules.filter((_, i) => i !== index).map((r, i) => ({ ...r, priority: i }));
    }

    function getTypeLabel(value: string): string {
        return ruleTypes.find(t => t.value === value)?.label ?? value;
    }

    function getOperatorLabel(value: string): string {
        return ruleOperators.find(o => o.value === value)?.label ?? value;
    }
</script>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <Label>Rules</Label>
        <Button type="button" variant="outline" size="sm" onclick={addRule}>
            Add rule
        </Button>
    </div>

    {#if rules.length === 0}
        <p class="text-sm text-muted-foreground">No rules yet. Add a rule to define matching criteria.</p>
    {/if}

    {#each rules as rule, index (index)}
        <div class="rounded-lg border p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-muted-foreground">Rule {index + 1}</span>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    class="h-6 px-2 text-xs text-destructive hover:text-destructive"
                    onclick={() => removeRule(index)}
                >
                    Remove
                </Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="grid gap-1.5">
                    <Label for="rule-type-{index}" class="text-xs">Type</Label>
                    <Select
                        type="single"
                        value={rule.type}
                        onValueChange={(v) => { if (v) rules[index].type = v; }}
                    >
                        <SelectTrigger class="w-full">
                            {getTypeLabel(rule.type)}
                        </SelectTrigger>
                        <SelectContent>
                            {#each ruleTypes as ruleType}
                                <SelectItem value={ruleType.value}>{ruleType.label}</SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>
                    <input type="hidden" name="rules[{index}][type]" value={rule.type} />
                    {#if errors[`rules.${index}.type`]}
                        <p class="text-xs text-destructive">{errors[`rules.${index}.type`]}</p>
                    {/if}
                </div>

                <div class="grid gap-1.5">
                    <Label for="rule-key-{index}" class="text-xs">Key</Label>
                    <Input
                        id="rule-key-{index}"
                        name="rules[{index}][key]"
                        bind:value={rule.key}
                        placeholder="e.g. utm_source, page_views"
                        class="h-9"
                    />
                    {#if errors[`rules.${index}.key`]}
                        <p class="text-xs text-destructive">{errors[`rules.${index}.key`]}</p>
                    {/if}
                </div>

                <div class="grid gap-1.5">
                    <Label for="rule-operator-{index}" class="text-xs">Operator</Label>
                    <Select
                        type="single"
                        value={rule.operator}
                        onValueChange={(v) => { if (v) rules[index].operator = v; }}
                    >
                        <SelectTrigger class="w-full">
                            {getOperatorLabel(rule.operator)}
                        </SelectTrigger>
                        <SelectContent>
                            {#each ruleOperators as op}
                                <SelectItem value={op.value}>{op.label}</SelectItem>
                            {/each}
                        </SelectContent>
                    </Select>
                    <input type="hidden" name="rules[{index}][operator]" value={rule.operator} />
                    {#if errors[`rules.${index}.operator`]}
                        <p class="text-xs text-destructive">{errors[`rules.${index}.operator`]}</p>
                    {/if}
                </div>

                <div class="grid gap-1.5">
                    <Label for="rule-value-{index}" class="text-xs">Value</Label>
                    <Input
                        id="rule-value-{index}"
                        name="rules[{index}][value]"
                        bind:value={rule.value}
                        placeholder="e.g. facebook, 5, en"
                        class="h-9"
                    />
                    {#if errors[`rules.${index}.value`]}
                        <p class="text-xs text-destructive">{errors[`rules.${index}.value`]}</p>
                    {/if}
                </div>
            </div>

            <input type="hidden" name="rules[{index}][priority]" value={rule.priority} />
        </div>
    {/each}
</div>
