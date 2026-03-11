import React from 'react';
import { cn } from '@/lib/utils';

interface AlertProps extends React.ComponentPropsWithoutRef<'div'> {
    variant?: 'default' | 'warning' | 'error' | 'info';
}

export function Alert({ variant = 'default', className, children, ...props }: AlertProps) {
    return (
        <div
            role="alert"
            className={cn(
                'relative w-full rounded-lg border p-4 text-sm',
                {
                    'border-[var(--border)] bg-[var(--card)] text-[var(--card-foreground)]': variant === 'default',
                    'border-amber-400/30 bg-amber-400/10 text-amber-400': variant === 'warning',
                    'border-red-400/30 bg-red-400/10 text-red-400': variant === 'error',
                    'border-blue-400/30 bg-blue-400/10 text-blue-400': variant === 'info',
                },
                className,
            )}
            {...props}
        >
            {children}
        </div>
    );
}

export function AlertTitle({ className, children, ...props }: React.ComponentPropsWithoutRef<'h5'>) {
    return (
        <h5 className={cn('mb-1 font-medium leading-none tracking-tight', className)} {...props}>
            {children}
        </h5>
    );
}

export function AlertDescription({ className, children, ...props }: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div className={cn('text-sm opacity-90', className)} {...props}>
            {children}
        </div>
    );
}
