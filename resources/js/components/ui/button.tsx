import React from 'react';
import { cn } from '@/lib/utils';

interface ButtonProps extends React.ComponentPropsWithoutRef<'button'> {
    variant?: 'default' | 'destructive' | 'ghost' | 'outline';
    size?: 'sm' | 'md' | 'lg';
}

export function Button({ variant = 'default', size = 'md', className, children, ...props }: ButtonProps) {
    return (
        <button
            className={cn(
                'inline-flex items-center justify-center rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ring)] disabled:pointer-events-none disabled:opacity-50',
                {
                    'bg-[var(--primary)] text-[var(--primary-foreground)] hover:bg-[var(--primary)]/90': variant === 'default',
                    'bg-[var(--destructive)] text-[var(--destructive-foreground)] hover:bg-[var(--destructive)]/90': variant === 'destructive',
                    'hover:bg-[var(--accent)] hover:text-[var(--accent-foreground)]': variant === 'ghost',
                    'border border-[var(--border)] bg-transparent hover:bg-[var(--accent)] hover:text-[var(--accent-foreground)]': variant === 'outline',
                    'h-7 px-2 text-xs': size === 'sm',
                    'h-9 px-4 text-sm': size === 'md',
                    'h-11 px-6 text-base': size === 'lg',
                },
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}
