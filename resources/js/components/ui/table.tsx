import React from 'react';
import { cn } from '@/lib/utils';

export function Table({ className, children, ...props }: React.ComponentPropsWithoutRef<'table'>) {
    return (
        <div className="w-full overflow-auto">
            <table className={cn('w-full caption-bottom text-sm', className)} {...props}>
                {children}
            </table>
        </div>
    );
}

export function TableHeader({ className, children, ...props }: React.ComponentPropsWithoutRef<'thead'>) {
    return (
        <thead className={cn('', className)} {...props}>
            {children}
        </thead>
    );
}

export function TableBody({ className, children, ...props }: React.ComponentPropsWithoutRef<'tbody'>) {
    return (
        <tbody className={cn('[&_tr:last-child]:border-0', className)} {...props}>
            {children}
        </tbody>
    );
}

export function TableRow({ className, children, ...props }: React.ComponentPropsWithoutRef<'tr'>) {
    return (
        <tr
            className={cn(
                'border-b border-[var(--border)] transition-colors hover:bg-[var(--muted)]/50 data-[state=selected]:bg-[var(--muted)]',
                className,
            )}
            {...props}
        >
            {children}
        </tr>
    );
}

export function TableHead({ className, children, ...props }: React.ComponentPropsWithoutRef<'th'>) {
    return (
        <th
            className={cn(
                'h-10 px-4 text-left align-middle text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]',
                className,
            )}
            {...props}
        >
            {children}
        </th>
    );
}

export function TableCell({ className, children, ...props }: React.ComponentPropsWithoutRef<'td'>) {
    return (
        <td className={cn('px-4 py-3 align-middle', className)} {...props}>
            {children}
        </td>
    );
}
