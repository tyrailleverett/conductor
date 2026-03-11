import React from 'react';
import { cn } from '@/lib/utils';

export function ScrollArea({ className, children, ...props }: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div
            className={cn('relative overflow-auto', className)}
            {...props}
        >
            {children}
        </div>
    );
}
