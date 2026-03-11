import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...classes: ClassValue[]): string {
    return twMerge(clsx(classes));
}

export function formatDuration(ms: number | null): string {
    if (ms === null) {
        return '—';
    }

    if (ms < 1000) {
        return `${ms}ms`;
    }

    const seconds = ms / 1000;
    if (seconds < 60) {
        return `${seconds.toFixed(1)}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}m ${remainingSeconds}s`;
}

export function formatRelativeTime(iso: string): string {
    const now = Date.now();
    const then = new Date(iso).getTime();
    const diffMs = now - then;
    const diffSeconds = Math.floor(diffMs / 1000);

    if (diffSeconds < 60) {
        return `${diffSeconds} seconds ago`;
    }

    const diffMinutes = Math.floor(diffSeconds / 60);
    if (diffMinutes < 60) {
        return `${diffMinutes} minute${diffMinutes === 1 ? '' : 's'} ago`;
    }

    const diffHours = Math.floor(diffMinutes / 60);
    if (diffHours < 24) {
        return `${diffHours} hour${diffHours === 1 ? '' : 's'} ago`;
    }

    const diffDays = Math.floor(diffHours / 24);
    return `${diffDays} day${diffDays === 1 ? '' : 's'} ago`;
}

export function statusColor(status: string): string {
    switch (status) {
        case 'completed':
            return 'text-green-400 bg-green-400/10';
        case 'running':
            return 'text-amber-400 bg-amber-400/10';
        case 'failed':
            return 'text-red-400 bg-red-400/10';
        case 'pending':
            return 'text-zinc-400 bg-zinc-400/10';
        case 'cancelled':
        case 'cancellation_requested':
            return 'text-zinc-400 bg-zinc-400/10';
        case 'waiting':
            return 'text-blue-400 bg-blue-400/10';
        case 'idle':
            return 'text-green-400 bg-green-400/10';
        case 'busy':
            return 'text-blue-400 bg-blue-400/10';
        case 'offline':
            return 'text-red-400 bg-red-400/10';
        default:
            return 'text-zinc-400 bg-zinc-400/10';
    }
}
