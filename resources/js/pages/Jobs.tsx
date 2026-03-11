import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router';
import { apiGet } from '@/lib/api';
import { formatRelativeTime, formatDuration } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorJob, PaginatedResponse, JobStatus } from '@/types';

const STATUS_OPTIONS: { value: string; label: string }[] = [
    { value: '', label: 'All statuses' },
    { value: 'pending', label: 'Pending' },
    { value: 'running', label: 'Running' },
    { value: 'completed', label: 'Completed' },
    { value: 'failed', label: 'Failed' },
    { value: 'cancelled', label: 'Cancelled' },
];

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
        <div className="space-y-4">
            <div>
                <h1 className="text-lg font-semibold">Jobs</h1>
                <p className="text-sm text-[var(--muted-foreground)]">{meta.total.toLocaleString()} total</p>
            </div>

            <div className="flex gap-3">
                <select
                    className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)]"
                    value={statusFilter}
                    onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
                >
                    {STATUS_OPTIONS.map((opt) => (
                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                </select>
                <select
                    className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)]"
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
                    className="rounded-md border border-[var(--border)] bg-[var(--card)] px-3 py-1.5 text-sm text-[var(--foreground)] placeholder:text-[var(--muted-foreground)]"
                    value={tagFilter}
                    onChange={(e) => { setTagFilter(e.target.value); setPage(1); }}
                />
            </div>

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
                                <TableHead>Queue</TableHead>
                                <TableHead>Attempts</TableHead>
                                <TableHead>Duration</TableHead>
                                <TableHead>Created</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {jobs.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={6} className="text-center text-[var(--muted-foreground)]">No jobs found.</TableCell>
                                </TableRow>
                            )}
                            {jobs.map((job) => (
                                <TableRow
                                    key={job.id}
                                    className="cursor-pointer"
                                    onClick={() => { navigate(`/jobs/${job.id}`); }}
                                >
                                    <TableCell><Badge variant="status" status={job.status}>{job.status}</Badge></TableCell>
                                    <TableCell className="font-mono text-xs">{job.display_name}</TableCell>
                                    <TableCell className="text-[var(--muted-foreground)]">{job.queue ?? '—'}</TableCell>
                                    <TableCell>{job.attempts}{job.max_attempts !== null ? `/${job.max_attempts}` : ''}</TableCell>
                                    <TableCell>{formatDuration(job.duration_ms)}</TableCell>
                                    <TableCell className="text-[var(--muted-foreground)]">{formatRelativeTime(job.created_at)}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
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
