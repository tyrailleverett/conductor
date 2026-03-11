import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
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
        <div className="space-y-4">
            <div>
                <h1 className="text-lg font-semibold">Events</h1>
                <p className="text-sm text-[var(--muted-foreground)]">{meta.total.toLocaleString()} total</p>
            </div>

            <input
                type="text"
                placeholder="Filter by event name..."
                className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] placeholder:text-[var(--muted-foreground)]"
                value={nameFilter}
                onChange={(e) => { setNameFilter(e.target.value); setPage(1); }}
            />

            {loading ? (
                <div className="space-y-2">
                    {[...Array(5)].map((_, i) => <Skeleton key={i} className="h-12 rounded" />)}
                </div>
            ) : (
                <>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Runs</TableHead>
                                <TableHead>Dispatched</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {events.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={3} className="text-center text-[var(--muted-foreground)]">No events found.</TableCell>
                                </TableRow>
                            )}
                            {events.map((event) => (
                                <TableRow key={event.id} className="cursor-pointer" onClick={() => { navigate(`/events/${event.id}`); }}>
                                    <TableCell className="font-mono text-xs">{event.name}</TableCell>
                                    <TableCell>{event.runs_count}</TableCell>
                                    <TableCell className="text-[var(--muted-foreground)]">{formatRelativeTime(event.dispatched_at)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                    <Pagination currentPage={meta.current_page} lastPage={meta.last_page} onPageChange={setPage} />
                </>
            )}
        </div>
    );
}
