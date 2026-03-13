import React, { useEffect, useState } from 'react';
import { apiGet } from '@/lib/api';
import { formatRelativeTime } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';
import { Layers, XCircle, GitFork, CheckCircle2 } from 'lucide-react';
import type { PaginatedResponse, ConductorEvent, ConductorJob, ConductorWorkflow, MetricsResponse } from '@/types';

interface OverviewStats {
    totalJobs: number;
    failedJobs: number;
    activeWorkflows: number;
    queueDepth: number;
}

interface ActivityItem {
    id: string;
    kind: 'job' | 'event';
    label: string;
    status: string;
    timestamp: string;
}

function StatusDot({ status }: { status: string }) {
    const color =
        status === 'completed' ? 'bg-green-400 text-green-400'
        : status === 'failed' ? 'bg-red-400 text-red-400'
        : status === 'running' ? 'bg-amber-400 text-amber-400'
        : 'bg-zinc-500 text-zinc-400';
    return (
        <span className="flex items-center gap-1.5">
            <span className={`inline-block h-2 w-2 rounded-full ${color.split(' ')[0]}`} />
            <span className={`capitalize ${color.split(' ')[1]}`}>{status}</span>
        </span>
    );
}

export default function Overview() {
    const [activityItems, setActivityItems] = useState<ActivityItem[]>([]);
    const [stats, setStats] = useState<OverviewStats | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function fetchData() {
            try {
                const [jobsRes, failedJobsRes, workflowsRes, metricsRes, eventsRes] = await Promise.all([
                    apiGet<PaginatedResponse<ConductorJob>>('/jobs?per_page=10'),
                    apiGet<PaginatedResponse<ConductorJob>>('/jobs?status=failed&per_page=1'),
                    apiGet<PaginatedResponse<ConductorWorkflow>>('/workflows?status=running'),
                    apiGet<MetricsResponse>('/metrics?window=1h'),
                    apiGet<PaginatedResponse<ConductorEvent>>('/events?per_page=10'),
                ]);

                const queueDepth = Object.values(metricsRes.queue_depth).reduce((sum, points) => {
                    const latest = points[points.length - 1];
                    return sum + (latest?.value ?? 0);
                }, 0);

                const recentActivity = [
                    ...jobsRes.data.map<ActivityItem>((job) => ({
                        id: `job-${job.id}`,
                        kind: 'job',
                        label: job.display_name,
                        status: job.status,
                        timestamp: job.created_at,
                    })),
                    ...eventsRes.data.map<ActivityItem>((event) => ({
                        id: `event-${event.id}`,
                        kind: 'event',
                        label: event.name,
                        status: 'event',
                        timestamp: event.dispatched_at,
                    })),
                ]
                    .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
                    .slice(0, 10);

                setStats({
                    totalJobs: jobsRes.meta.total,
                    failedJobs: failedJobsRes.meta.total,
                    activeWorkflows: workflowsRes.meta.total,
                    queueDepth,
                });
                setActivityItems(recentActivity);
            } finally {
                setLoading(false);
            }
        }

        fetchData();
    }, []);

    if (loading) {
        return (
            <div className="space-y-6">
                <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    {[...Array(4)].map((_, i) => (
                        <Skeleton key={i} className="h-24 rounded-lg" />
                    ))}
                </div>
                <Skeleton className="h-48 rounded-lg" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-xl font-semibold text-[var(--foreground)]">Overview</h1>
                <p className="text-sm text-[var(--muted-foreground)]">System summary and recent activity</p>
            </div>

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Total Jobs" value={stats?.totalJobs ?? 0} icon={<Layers className="h-5 w-5 text-zinc-500" />} />
                <StatCard label="Failed Jobs" value={stats?.failedJobs ?? 0} icon={<XCircle className="h-5 w-5 text-red-400" />} valueClass="text-[var(--foreground)]" />
                <StatCard label="Active Workflows" value={stats?.activeWorkflows ?? 0} icon={<GitFork className="h-5 w-5 text-zinc-500" />} />
                <StatCard label="Queue Depth" value={stats?.queueDepth ?? 0} icon={<CheckCircle2 className="h-5 w-5 text-zinc-500" />} />
            </div>

            <div>
                <h2 className="mb-3 text-sm font-medium text-[var(--foreground)]">Recent Activity</h2>
                <div className="rounded-lg border border-[var(--border)] overflow-hidden">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-[var(--border)]">
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Name</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Status</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Type</th>
                                <th className="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[var(--muted-foreground)]">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            {activityItems.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-6 text-center text-[var(--muted-foreground)]">No recent activity.</td>
                                </tr>
                            )}
                            {activityItems.map((item, idx) => (
                                <tr
                                    key={item.id}
                                    className={`border-b border-[var(--border)] last:border-0 hover:bg-[var(--muted)]/30 transition-colors ${idx % 2 === 0 ? '' : ''}`}
                                >
                                    <td className="px-4 py-3 font-mono text-sm text-[var(--foreground)]">{item.label}</td>
                                    <td className="px-4 py-3">
                                        <StatusDot status={item.status} />
                                    </td>
                                    <td className="px-4 py-3 text-[var(--muted-foreground)] capitalize">{item.kind}</td>
                                    <td className="px-4 py-3 text-[var(--muted-foreground)]">{formatRelativeTime(item.timestamp)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
}

function StatCard({ label, value, icon, valueClass = '' }: { label: string; value: number; icon: React.ReactNode; valueClass?: string }) {
    return (
        <div className="rounded-lg border border-[var(--border)] bg-[var(--card)] p-5">
            <div className="flex items-center justify-between mb-3">
                {icon}
            </div>
            <div className={`text-3xl font-bold text-[var(--foreground)] ${valueClass}`}>{value.toLocaleString()}</div>
            <p className="text-xs text-[var(--muted-foreground)] mt-1">{label}</p>
        </div>
    );
}
