import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Pagination } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
import { Search } from 'lucide-react';
import type { ConductorEvent, PaginatedResponse } from '@/types';

export default function Events() {
    const navigate = useNavigate();
    const [events, setEvents] = useState<ConductorEvent[]>([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
    const [loading, setLoading] = useState(true);
    const [nameFilter, setNameFilter] = useState('');
    const [page, setPage] = useState(1);

    useEffect(() => {
        async function fetchEvents() {
            setLoading(true);
            try {
                const params = new URLSearchParams({ page: String(page), per_page: '20' });
                if (nameFilter) { params.set('name', nameFilter); }
                const res = await apiGet<PaginatedResponse<ConductorEvent>>(`/events?${params.toString()}`);
                setEvents(res.data);
                setMeta(res.meta);
            } finally {
                setLoading(false);
            }
        }

        fetchEvents();
    }, [nameFilter, page]);

    return (
        <div className="space-y-5">
            <div>
                <h1 className="text-xl font-semibold text-[var(--foreground)]">Events</h1>
                <p className="text-sm text-[var(--muted-foreground)]">Browse all dispatched events</p>
            </div>

            {/* Search input with icon */}
            <div className="relative w-72">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-[var(--muted-foreground)]" />
                <input
                    type="text"
                    placeholder="Filter by event name..."
                    className="w-full rounded-md border border-[var(--border)] bg-[var(--card)] pl-8 pr-3 py-1.5 font-mono text-sm text-[var(--foreground)] placeholder:text-[var(--muted-foreground)] focus:outline-none focus:ring-1 focus:ring-[var(--ring)]"
                    value={nameFilter}
                    onChange={(e) => { setNameFilter(e.target.value); setPage(1); }}
                />
            </div>

            {loading ? (
                <div className="space-y-2">
                    {[...Array(5)].map((_, i) => <Skeleton key={i} className="h-12 rounded" />)}
                </div>
            ) : (
                <>
                    <div className="rounded-lg border border-[var(--border)] overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-[var(--border)]">
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Event Name</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Runs</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Dispatched</th>
                                </tr>
                            </thead>
                            <tbody>
                                {events.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No events found.</td>
                                    </tr>
                                )}
                                {events.map((event) => (
                                    <tr
                                        key={event.id}
                                        className="border-b border-[var(--border)] last:border-0 hover:bg-[var(--muted)]/30 cursor-pointer transition-colors"
                                        onClick={() => { navigate(`/events/${event.id}`); }}
                                    >
                                        <td className="px-4 py-3 font-mono text-sm text-[var(--foreground)]">{event.name}</td>
                                        <td className="px-4 py-3 text-[var(--foreground)]">{event.runs_count}</td>
                                        <td className="px-4 py-3 text-[var(--muted-foreground)]">{formatRelativeTime(event.dispatched_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination currentPage={meta.current_page} lastPage={meta.last_page} onPageChange={setPage} />
                </>
            )}
        </div>
    );
}
