import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
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

function StatusDot({ status }: { status: string }) {
    const color =
        status === 'completed' ? 'bg-green-400 text-green-400'
        : status === 'failed' ? 'bg-red-400 text-red-400'
        : status === 'running' ? 'bg-amber-400 text-amber-400'
        : 'bg-zinc-500 text-zinc-400';
    return (
        <span className="flex items-center gap-1.5 text-sm">
            <span className={`inline-block h-1.5 w-1.5 rounded-full ${color.split(' ')[0]}`} />
            <span className={`capitalize ${color.split(' ')[1]}`}>{status}</span>
        </span>
    );
}

function StepsBar({ current, total, status }: { current: number; total: number; status: string }) {
    const pct = total > 0 ? Math.round((current / total) * 100) : 0;
    const barColor =
        status === 'completed' ? 'bg-green-400'
        : status === 'failed' ? 'bg-red-400'
        : status === 'running' ? 'bg-green-400'
        : 'bg-zinc-600';
    return (
        <div className="flex items-center gap-2">
            <span className="text-sm text-[var(--foreground)]">{current}/{total}</span>
            <div className="h-1 w-24 rounded-full bg-[var(--border)]">
                <div className={`h-1 rounded-full ${barColor} transition-all`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}

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
        <div className="space-y-5">
            <div>
                <h1 className="text-xl font-semibold text-[var(--foreground)]">Workflows</h1>
                <p className="text-sm text-[var(--muted-foreground)]">Browse all workflow runs</p>
            </div>

            <div className="flex items-center gap-3">
                <select
                    className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] focus:outline-none focus:ring-1 focus:ring-[var(--ring)]"
                    value={statusFilter}
                    onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
                >
                    {STATUS_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
            </div>

            {loading ? (
                <div className="space-y-2">
                    {[...Array(5)].map((_, i) => <Skeleton key={i} className="h-14 rounded" />)}
                </div>
            ) : (
                <>
                    <div className="rounded-lg border border-[var(--border)] overflow-hidden">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-[var(--border)]">
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Name</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Status</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Steps</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Created</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                {workflows.length === 0 && (
                                    <tr>
                                        <td colSpan={5} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No workflows found.</td>
                                    </tr>
                                )}
                                {workflows.map((wf) => (
                                    <tr
                                        key={wf.id}
                                        className="border-b border-[var(--border)] last:border-0 hover:bg-[var(--muted)]/30 cursor-pointer transition-colors"
                                        onClick={() => { navigate(`/workflows/${wf.id}`); }}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-mono text-sm text-[var(--foreground)]">{wf.display_name}</div>
                                            <div className="mt-0.5 font-mono text-xs text-[var(--muted-foreground)]">{wf.class}</div>
                                        </td>
                                        <td className="px-4 py-3"><StatusDot status={wf.status} /></td>
                                        <td className="px-4 py-3">
                                            <StepsBar current={wf.current_step_index} total={wf.step_count} status={wf.status} />
                                        </td>
                                        <td className="px-4 py-3 text-[var(--muted-foreground)]">{formatRelativeTime(wf.created_at)}</td>
                                        <td className="px-4 py-3 text-[var(--muted-foreground)]">
                                            {wf.completed_at
                                                ? formatRelativeTime(wf.completed_at)
                                                : wf.cancelled_at
                                                    ? formatRelativeTime(wf.cancelled_at)
                                                    : '—'}
                                        </td>
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
