import React, { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

interface DropdownMenuProps {
    trigger: React.ReactNode;
    children: React.ReactNode;
    className?: string;
}

export function DropdownMenu({ trigger, children, className }: DropdownMenuProps) {
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => { document.removeEventListener('mousedown', handleClickOutside); };
    }, []);

    return (
        <div ref={ref} className={cn('relative inline-block', className)}>
            <div onClick={() => { setOpen(!open); }} className="cursor-pointer">
                {trigger}
            </div>
            {open && (
                <div className="absolute right-0 z-50 mt-1 min-w-[8rem] overflow-hidden rounded-md border border-[var(--border)] bg-[var(--popover)] shadow-lg">
                    <div onClick={() => { setOpen(false); }}>
                        {children}
                    </div>
                </div>
            )}
        </div>
    );
}

export function DropdownMenuItem({ className, children, ...props }: React.ComponentPropsWithoutRef<'button'>) {
    return (
        <button
            className={cn(
                'flex w-full cursor-pointer items-center px-3 py-2 text-sm text-[var(--foreground)] transition-colors hover:bg-[var(--accent)] focus:bg-[var(--accent)] focus:outline-none',
                className,
            )}
            {...props}
        >
            {children}
        </button>
    );
}
