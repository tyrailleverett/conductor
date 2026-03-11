import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorWorkflow, PaginatedResponse } from '@/types';

const STATUS_OPTIONS = [
    { value: '', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'running', label: 'Running' },
    { value: 'waiting', label: 'Waiting' },
    { value: 'completed', label: 'Completed' },
    { value: 'failed', label: 'Failed' },
    { value: 'cancelled', label: 'Cancelled' },
];

export default function Workflows() {
    const navigate = useNavigate();
    const [workflows, setWorkflows] = useState<ConductorWorkflow[]>([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('');
    const [page, setPage] = useState(1);

    useEffect(() => {
        async function fetchWorkflows() {
            setLoading(true);
            try {
                const params = new URLSearchParams({ page: String(page), per_page: '20' });
                if (statusFilter) { params.set('status', statusFilter); }
                const res = await apiGet<PaginatedResponse<ConductorWorkflow>>(`/workflows?${params.toString()}`);
                setWorkflows(res.data);
                setMeta(res.meta);
            } finally {
                setLoading(false);
            }
        }

        fetchWorkflows();
    }, [statusFilter, page]);

    return (
        <div className="space-y-4">
            <div>
                <h1 className="text-lg font-semibold">Workflows</h1>
                <p className="text-sm text-[var(--muted-foreground)]">{meta.total.toLocaleString()} total</p>
            </div>

            <select
                className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)]"
                value={statusFilter}
                onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
            >
                {STATUS_OPTIONS.map((opt) => (
                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                ))}
            </select>

            {loading ? (
                <div className="space-y-2">
                    {[...Array(5)].map((_, i) => <Skeleton key={i} className="h-12 rounded" />)}
                </div>
            ) : (
                <>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Status</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Steps</TableHead>
                                <TableHead>Created</TableHead>
                                <TableHead>Completed / Cancelled</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {workflows.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-[var(--muted-foreground)]">No workflows found.</TableCell>
                                </TableRow>
                            )}
                            {workflows.map((wf) => (
                                <TableRow key={wf.id} className="cursor-pointer" onClick={() => { navigate(`/workflows/${wf.id}`); }}>
                                    <TableCell><Badge variant="status" status={wf.status}>{wf.status}</Badge></TableCell>
                                    <TableCell className="font-mono text-xs">{wf.display_name}</TableCell>
                                    <TableCell>{wf.current_step_index}/{wf.step_count}</TableCell>
                                    <TableCell className="text-[var(--muted-foreground)]">{formatRelativeTime(wf.created_at)}</TableCell>
                                    <TableCell className="text-[var(--muted-foreground)]">
                                        {wf.completed_at ? formatRelativeTime(wf.completed_at) : wf.cancelled_at ? formatRelativeTime(wf.cancelled_at) : '—'}
                                    </TableCell>
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
