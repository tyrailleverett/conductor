import React from 'react';
import { cn } from '@/lib/utils';

export function Separator({ className, orientation = 'horizontal', ...props }: React.ComponentPropsWithoutRef<'div'> & { orientation?: 'horizontal' | 'vertical' }) {
    return (
        <div
            role="separator"
            className={cn(
                'shrink-0 bg-[var(--border)]',
                orientation === 'horizontal' ? 'h-px w-full' : 'h-full w-px',
                className,
            )}
            {...props}
        />
    );
}
