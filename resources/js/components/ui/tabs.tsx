import React from 'react';
import { cn } from '@/lib/utils';

interface TabsListProps extends React.ComponentPropsWithoutRef<'div'> { }

interface SimpleTabsProps {
    tabs: { value: string; label: string }[];
    value: string;
    onValueChange: (value: string) => void;
    className?: string;
}

export function SimpleTabs({ tabs, value, onValueChange, className }: SimpleTabsProps) {
    return (
        <div className={cn('inline-flex items-center rounded-md bg-[var(--muted)] p-1', className)}>
            {tabs.map((tab) => (
                <button
                    key={tab.value}
                    className={cn(
                        'inline-flex items-center justify-center rounded px-3 py-1 text-sm font-medium transition-colors',
                        value === tab.value
                            ? 'bg-[var(--background)] text-[var(--foreground)] shadow-sm'
                            : 'text-[var(--muted-foreground)] hover:text-[var(--foreground)]',
                    )}
                    onClick={() => { onValueChange(tab.value); }}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
