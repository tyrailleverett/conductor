import React from 'react';
import { cn, statusColor } from '@/lib/utils';

interface BadgeProps extends React.ComponentPropsWithoutRef<'span'> {
    variant?: 'status' | 'default';
    status?: string;
}

export function Badge({ variant = 'default', status, className, children, ...props }: BadgeProps) {
    const colorClass = variant === 'status' && status ? statusColor(status) : 'text-zinc-400 bg-zinc-400/10';

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium uppercase tracking-wide',
                colorClass,
                className,
            )}
            {...props}
        >
            {children}
        </span>
    );
}
