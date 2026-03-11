import React from 'react';
import { cn } from '@/lib/utils';

export function Card({ className, children, ...props }: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div className={cn('rounded-lg border border-[var(--border)] bg-[var(--card)] text-[var(--card-foreground)]', className)} {...props}>
            {children}
        </div>
    );
}

export function CardHeader({ className, children, ...props }: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div className={cn('flex flex-col space-y-1.5 p-4', className)} {...props}>
            {children}
        </div>
    );
}

export function CardTitle({ className, children, ...props }: React.ComponentPropsWithoutRef<'h3'>) {
    return (
        <h3 className={cn('text-sm font-semibold leading-none tracking-tight text-[var(--muted-foreground)]', className)} {...props}>
            {children}
        </h3>
    );
}

export function CardContent({ className, children, ...props }: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div className={cn('p-4 pt-0', className)} {...props}>
            {children}
        </div>
    );
}

export function CardFooter({ className, children, ...props }: React.ComponentPropsWithoutRef<'div'>) {
    return (
        <div className={cn('flex items-center p-4 pt-0', className)} {...props}>
            {children}
        </div>
    );
}
