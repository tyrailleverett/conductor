import React from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

interface PaginationProps {
    currentPage: number;
    lastPage: number;
    onPageChange: (page: number) => void;
    className?: string;
}

export function Pagination({ currentPage, lastPage, onPageChange, className }: PaginationProps) {
    const hasPrev = currentPage > 1;
    const hasNext = currentPage < lastPage;

    return (
        <div className={cn('flex items-center justify-between', className)}>
            <p className="text-sm text-[var(--muted-foreground)]">
                Page {currentPage} of {lastPage}
            </p>
            <div className="flex gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!hasPrev}
                    onClick={() => { onPageChange(currentPage - 1); }}
                >
                    Previous
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!hasNext}
                    onClick={() => { onPageChange(currentPage + 1); }}
                >
                    Next
                </Button>
            </div>
        </div>
    );
}
