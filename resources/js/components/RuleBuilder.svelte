<script lang="ts">
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuGroup,
        DropdownMenuItem,
        DropdownMenuLabel,
        DropdownMenuSeparator,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
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

    interface RuleTemplateItem {
        id: number;
        name: string;
        type: string;
        key: string;
        operator: string;
        value: string;
    }

    let {
        rules = $bindable([]),
        ruleTypes,
        ruleOperators,
        ruleTemplates = [],
        errors = {},
    }: {
        rules: Rule[];
        ruleTypes: EnumOption[];
        ruleOperators: EnumOption[];
        ruleTemplates?: RuleTemplateItem[];
        errors: Record<string, string>;
    } = $props();

    const keyDefaults: Record<string, string> = {
        browser_language: 'Accept-Language',
        visit_count: 'page-view',
    };

    const keyPlaceholders: Record<string, string> = {
        comparison: 'e.g. utm_source, page_path, referrer_url',
        browser_language: 'HTTP header name',
        visit_count: 'Event type to count',
    };

    const keyHints: Record<string, string> = {
        comparison:
            'Event log field name (e.g. utm_source, utm_campaign, page_path)',
        browser_language: 'Request header to match against',
        visit_count: 'The event type to count (e.g. page-view, add-to-cart)',
    };

    function shouldShowKey(type: string): boolean {
        return type !== 'page_view_count';
    }

    function addRule(): void {
        const type = ruleTypes[0]?.value ?? '';
        rules = [
            ...rules,
            {
                type,
                key: keyDefaults[type] ?? '',
                operator: ruleOperators[0]?.value ?? '',
                value: '',
                priority: rules.length,
            },
        ];
    }

    function addFromTemplate(template: RuleTemplateItem): void {
        rules = [
            ...rules,
            {
                type: template.type,
                key: template.key,
                operator: template.operator,
                value: template.value,
                priority: rules.length,
            },
        ];
    }

    function onTypeChange(index: number, newType: string): void {
        rules[index].type = newType;

        if (newType in keyDefaults) {
            rules[index].key = keyDefaults[newType];
        } else if (newType === 'page_view_count') {
            rules[index].key = '';
        }
    }

    function removeRule(index: number): void {
        rules = rules
            .filter((_, i) => i !== index)
            .map((r, i) => ({ ...r, priority: i }));
    }

    function getTypeLabel(value: string): string {
        return ruleTypes.find((t) => t.value === value)?.label ?? value;
    }

    function getOperatorLabel(value: string): string {
        return ruleOperators.find((o) => o.value === value)?.label ?? value;
    }
</script>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <Label>Rules</Label>
        <div class="flex items-center gap-1">
            {#if ruleTemplates.length > 0}
                <DropdownMenu>
                    <DropdownMenuTrigger>
                        <Button type="button" variant="outline" size="sm">
                            From template
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-64">
                        <DropdownMenuLabel>Rule templates</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuGroup>
                            {#each ruleTemplates as template (template.id)}
                                <DropdownMenuItem
                                    onclick={() => addFromTemplate(template)}
                                >
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm"
                                            >{template.name}</span
                                        >
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            {getTypeLabel(template.type)}
                                            {#if template.key}
                                                &middot; {template.key}
                                            {/if}
                                            &middot; {getOperatorLabel(
                                                template.operator,
                                            )}
                                            {#if template.value}
                                                &middot; {template.value}
                                            {/if}
                                        </span>
                                    </div>
                                </DropdownMenuItem>
                            {/each}
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            {/if}
            <Button type="button" variant="outline" size="sm" onclick={addRule}>
                Add blank rule
            </Button>
        </div>
    </div>

    {#if rules.length === 0}
        <p class="text-sm text-muted-foreground">
            No rules yet. Add a rule to define matching criteria.
        </p>
    {/if}

    {#each rules as rule, index (index)}
        <div class="rounded-lg border p-4 space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-muted-foreground"
                    >Rule {index + 1}</span
                >
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
                        onValueChange={(v) => {
                            if (v) {
                                onTypeChange(index, v);
                            }
                        }}
                    >
                        <SelectTrigger class="w-full">
                            {getTypeLabel(rule.type)}
                        </SelectTrigger>
                        <SelectContent>
                            {#each ruleTypes as ruleType (ruleType.value)}
                                <SelectItem value={ruleType.value}
                                    >{ruleType.label}</SelectItem
                                >
                            {/each}
                        </SelectContent>
                    </Select>
                    <input
                        type="hidden"
                        name="rules[{index}][type]"
                        value={rule.type}
                    />
                    {#if errors[`rules.${index}.type`]}
                        <p class="text-xs text-destructive">
                            {errors[`rules.${index}.type`]}
                        </p>
                    {/if}
                </div>

                {#if shouldShowKey(rule.type)}
                    <div class="grid gap-1.5">
                        <Label for="rule-key-{index}" class="text-xs">Key</Label
                        >
                        <Input
                            id="rule-key-{index}"
                            name="rules[{index}][key]"
                            bind:value={rule.key}
                            placeholder={keyPlaceholders[rule.type] ?? 'Key'}
                            class="h-9"
                        />
                        {#if keyHints[rule.type]}
                            <p class="text-xs text-muted-foreground">
                                {keyHints[rule.type]}
                            </p>
                        {/if}
                        {#if errors[`rules.${index}.key`]}
                            <p class="text-xs text-destructive">
                                {errors[`rules.${index}.key`]}
                            </p>
                        {/if}
                    </div>
                {:else}
                    <input type="hidden" name="rules[{index}][key]" value="" />
                {/if}

                <div class="grid gap-1.5">
                    <Label for="rule-operator-{index}" class="text-xs"
                        >Operator</Label
                    >
                    <Select
                        type="single"
                        value={rule.operator}
                        onValueChange={(v) => {
                            if (v) {
                                rules[index].operator = v;
                            }
                        }}
                    >
                        <SelectTrigger class="w-full">
                            {getOperatorLabel(rule.operator)}
                        </SelectTrigger>
                        <SelectContent>
                            {#each ruleOperators as op (op.value)}
                                <SelectItem value={op.value}
                                    >{op.label}</SelectItem
                                >
                            {/each}
                        </SelectContent>
                    </Select>
                    <input
                        type="hidden"
                        name="rules[{index}][operator]"
                        value={rule.operator}
                    />
                    {#if errors[`rules.${index}.operator`]}
                        <p class="text-xs text-destructive">
                            {errors[`rules.${index}.operator`]}
                        </p>
                    {/if}
                </div>

                <div class="grid gap-1.5">
                    <Label for="rule-value-{index}" class="text-xs">Value</Label
                    >
                    <Input
                        id="rule-value-{index}"
                        name="rules[{index}][value]"
                        bind:value={rule.value}
                        placeholder="e.g. facebook, 5, en"
                        class="h-9"
                    />
                    {#if errors[`rules.${index}.value`]}
                        <p class="text-xs text-destructive">
                            {errors[`rules.${index}.value`]}
                        </p>
                    {/if}
                </div>
            </div>

            <input
                type="hidden"
                name="rules[{index}][priority]"
                value={rule.priority}
            />
        </div>
    {/each}
</div>
