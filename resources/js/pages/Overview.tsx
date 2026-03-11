import React, { useEffect, useState } from 'react';
import { apiGet } from '@/lib/api';
import { formatRelativeTime, formatDuration } from '@/lib/utils';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
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
    durationMs: number | null;
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
                        durationMs: job.duration_ms,
                    })),
                    ...eventsRes.data.map<ActivityItem>((event) => ({
                        id: `event-${event.id}`,
                        kind: 'event',
                        label: event.name,
                        status: 'event',
                        timestamp: event.dispatched_at,
                        durationMs: null,
                    })),
                ]
                    .sort((leftItem, rightItem) => new Date(rightItem.timestamp).getTime() - new Date(leftItem.timestamp).getTime())
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
                <h1 className="text-lg font-semibold">Overview</h1>
                <p className="text-sm text-[var(--muted-foreground)]">Queue worker activity at a glance</p>
            </div>

            <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Total Jobs" value={stats?.totalJobs ?? 0} icon="⚡" />
                <StatCard label="Failed Jobs" value={stats?.failedJobs ?? 0} icon="✕" valueClass="text-red-400" />
                <StatCard label="Active Workflows" value={stats?.activeWorkflows ?? 0} icon="⇄" valueClass="text-amber-400" />
                <StatCard label="Queue Depth" value={stats?.queueDepth ?? 0} icon="☰" />
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="space-y-2">
                        {activityItems.length === 0 && (
                            <p className="text-sm text-[var(--muted-foreground)]">No recent activity.</p>
                        )}
                        {activityItems.map((item) => (
                            <div key={item.id} className="flex items-center justify-between border-b border-[var(--border)] py-2 last:border-0">
                                <div className="flex items-center gap-3">
                                    {item.kind === 'job' ? (
                                        <Badge variant="status" status={item.status}>{item.status}</Badge>
                                    ) : (
                                        <Badge>event</Badge>
                                    )}
                                    <span className="font-mono text-xs text-[var(--foreground)]">{item.label}</span>
                                </div>
                                <div className="flex items-center gap-4 text-xs text-[var(--muted-foreground)]">
                                    {item.durationMs !== null && <span>{formatDuration(item.durationMs)}</span>}
                                    <span>{formatRelativeTime(item.timestamp)}</span>
                                </div>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}

function StatCard({ label, value, icon, valueClass = '' }: { label: string; value: number; icon: string; valueClass?: string }) {
    return (
        <Card>
            <CardContent className="pt-4">
                <div className="flex items-center justify-between">
                    <span className="text-xs text-[var(--muted-foreground)]">{icon}</span>
                </div>
                <div className={`mt-2 text-2xl font-bold ${valueClass}`}>{value.toLocaleString()}</div>
                <p className="text-xs text-[var(--muted-foreground)] mt-1">{label}</p>
            </CardContent>
        </Card>
    );
}
