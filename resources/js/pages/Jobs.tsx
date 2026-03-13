import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime, formatDuration } from '@/lib/utils';
import { Pagination } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
import { SlidersHorizontal } from 'lucide-react';
import type { ConductorJob, PaginatedResponse } from '@/types';

const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: '', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'running', label: 'Running' },
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

const selectClass = 'rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] focus:outline-none focus:ring-1 focus:ring-[var(--ring)]';

export default function Jobs() {
    const navigate = useNavigate();
    const [jobs, setJobs] = useState<ConductorJob[]>([]);
    const [meta, setMeta] = useState({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('');
    const [queueFilter, setQueueFilter] = useState('');
    const [tagFilter, setTagFilter] = useState('');
    const [page, setPage] = useState(1);

    useEffect(() => {
        async function fetchJobs() {
            setLoading(true);
            try {
                const params = new URLSearchParams({ page: String(page), per_page: '20' });
                if (statusFilter) { params.set('status', statusFilter); }
                if (queueFilter) { params.set('queue', queueFilter); }
                if (tagFilter) { params.set('tag', tagFilter); }

                const res = await apiGet<PaginatedResponse<ConductorJob>>(`/jobs?${params.toString()}`);
                setJobs(res.data);
                setMeta(res.meta);
            } finally {
                setLoading(false);
            }
        }

        fetchJobs();
    }, [statusFilter, queueFilter, tagFilter, page]);

    const queues = [...new Set(jobs.map((j) => j.queue).filter(Boolean))] as string[];

    return (
        <div className="space-y-5">
            <div>
                <h1 className="text-xl font-semibold text-[var(--foreground)]">Jobs</h1>
                <p className="text-sm text-[var(--muted-foreground)]">Browse and filter all tracked job executions</p>
            </div>

            <div className="flex items-center gap-3">
                <SlidersHorizontal className="h-4 w-4 text-[var(--muted-foreground)] shrink-0" />
                <select
                    className={selectClass}
                    value={statusFilter}
                    onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
                >
                    {STATUS_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
                <select
                    className={selectClass}
                    value={queueFilter}
                    onChange={(e) => { setQueueFilter(e.target.value); setPage(1); }}
                >
                    <option value="">All queues</option>
                    {queues.map((q) => (
                        <option key={q} value={q}>{q}</option>
                    ))}
                </select>
                <input
                    type="text"
                    placeholder="Filter by tag..."
                    className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] placeholder:text-[var(--muted-foreground)] focus:outline-none focus:ring-1 focus:ring-[var(--ring)]"
                    value={tagFilter}
                    onChange={(e) => { setTagFilter(e.target.value); setPage(1); }}
                />
                <span className="ml-auto text-sm text-[var(--muted-foreground)]">{meta.total.toLocaleString()} jobs</span>
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
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Queue</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Attempts</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Duration</th>
                                    <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                {jobs.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No jobs found.</td>
                                    </tr>
                                )}
                                {jobs.map((job) => (
                                    <tr
                                        key={job.id}
                                        className="border-b border-[var(--border)] last:border-0 hover:bg-[var(--muted)]/30 cursor-pointer transition-colors"
                                        onClick={() => { navigate(`/jobs/${job.id}`); }}
                                    >
                                        <td className="px-4 py-3">
                                            <div className="font-mono text-sm text-[var(--foreground)]">{job.display_name}</div>
                                            {job.tags.length > 0 && (
                                                <div className="mt-1 flex flex-wrap gap-1">
                                                    {job.tags.map((tag) => (
                                                        <span key={tag} className="rounded border border-[var(--border)] bg-[var(--muted)] px-1.5 py-0.5 font-mono text-[10px] text-[var(--muted-foreground)]">
                                                            {tag}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </td>
                                        <td className="px-4 py-3"><StatusDot status={job.status} /></td>
                                        <td className="px-4 py-3 text-[var(--muted-foreground)]">{job.queue ?? '—'}</td>
                                        <td className="px-4 py-3 text-[var(--foreground)]">
                                            {job.attempts}{job.max_attempts !== null ? `/${job.max_attempts}` : ''}
                                        </td>
                                        <td className="px-4 py-3 text-[var(--foreground)]">{formatDuration(job.duration_ms)}</td>
                                        <td className="px-4 py-3 text-[var(--muted-foreground)]">{formatRelativeTime(job.created_at)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <Pagination
                        currentPage={meta.current_page}
                        lastPage={meta.last_page}
                        onPageChange={setPage}
                    />
                </>
            )}
        </div>
    );
}
