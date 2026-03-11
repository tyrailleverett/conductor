import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router';
import { apiGet, apiDelete } from '@/lib/api';
import { formatRelativeTime, formatDuration } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { ConductorWorkflow, ConductorWorkflowStep } from '@/types';

export default function WorkflowDetail() {
    const { id } = useParams<{ id: string }>();
    const [workflow, setWorkflow] = useState<ConductorWorkflow | null>(null);
    const [loading, setLoading] = useState(true);
    const [cancelLoading, setCancelLoading] = useState(false);
    const [openOutputs, setOpenOutputs] = useState<Set<number>>(new Set());

    async function loadWorkflow() {
        if (!id) { return; }
        setLoading(true);
        try {
            const res = await apiGet<{ data: ConductorWorkflow }>(`/workflows/${id}`);
            setWorkflow(res.data);
        } finally {
            setLoading(false);
        }
    }

    useEffect(() => { loadWorkflow(); }, [id]);

    async function handleCancel() {
        if (!id) { return; }
        setCancelLoading(true);
        try {
            await apiDelete(`/workflows/${id}`);
            await loadWorkflow();
        } finally {
            setCancelLoading(false);
        }
    }

    function toggleOutput(stepId: number) {
        setOpenOutputs((prev) => {
            const next = new Set(prev);
            if (next.has(stepId)) { next.delete(stepId); } else { next.add(stepId); }
            return next;
        });
    }

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-64" />
                <Skeleton className="h-32 rounded-lg" />
            </div>
        );
    }

    if (!workflow) {
        return <p className="text-[var(--muted-foreground)]">Workflow not found.</p>;
    }

    const canCancel = workflow.status === 'running' || workflow.status === 'waiting' || workflow.status === 'pending';

    return (
        <div className="space-y-6">
            <div className="flex items-start justify-between">
                <div>
                    <h1 className="font-mono text-sm font-semibold">{workflow.display_name}</h1>
                    <p className="text-xs text-[var(--muted-foreground)] mt-1">{workflow.id}</p>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant="status" status={workflow.status}>{workflow.status}</Badge>
                    {canCancel && (
                        <Button size="sm" variant="destructive" onClick={handleCancel} disabled={cancelLoading}>Cancel</Button>
                    )}
                </div>
            </div>

            <Card>
                <CardHeader><CardTitle>Metadata</CardTitle></CardHeader>
                <CardContent>
                    <dl className="grid grid-cols-2 gap-x-8 gap-y-3 text-sm md:grid-cols-3">
                        <div>
                            <dt className="text-xs text-[var(--muted-foreground)] uppercase tracking-wider">Class</dt>
                            <dd className="mt-1 font-mono text-xs">{workflow.class}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-[var(--muted-foreground)] uppercase tracking-wider">Created</dt>
                            <dd className="mt-1 text-sm">{formatRelativeTime(workflow.created_at)}</dd>
                        </div>
                        <div>
                            <dt className="text-xs text-[var(--muted-foreground)] uppercase tracking-wider">Completed</dt>
                            <dd className="mt-1 text-sm">{workflow.completed_at ? formatRelativeTime(workflow.completed_at) : '—'}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            {(workflow.steps ?? []).length > 0 && (
                <Card>
                    <CardHeader><CardTitle>Steps</CardTitle></CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {(workflow.steps ?? []).map((step) => (
                                <StepRow
                                    key={step.id}
                                    step={step}
                                    outputOpen={openOutputs.has(step.id)}
                                    onToggleOutput={() => { toggleOutput(step.id); }}
                                />
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}

function StepRow({ step, outputOpen, onToggleOutput }: { step: ConductorWorkflowStep; outputOpen: boolean; onToggleOutput: () => void }) {
    return (
        <div className="flex gap-4">
            <div className="flex flex-col items-center">
                <div className={`flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ${step.status === 'completed' ? 'bg-green-400/20 text-green-400' : step.status === 'failed' ? 'bg-red-400/20 text-red-400' : step.status === 'running' ? 'bg-amber-400/20 text-amber-400' : 'bg-zinc-400/10 text-zinc-400'}`}>
                    {step.step_index + 1}
                </div>
                <div className="w-px flex-1 bg-[var(--border)] mt-1" />
            </div>
            <div className="flex-1 pb-4">
                <div className="flex items-center gap-2">
                    <span className="text-sm font-medium">{step.name}</span>
                    <Badge variant="status" status={step.status}>{step.status}</Badge>
                    <span className="text-xs text-[var(--muted-foreground)]">{formatDuration(step.duration_ms)}</span>
                    <span className="text-xs text-[var(--muted-foreground)]">×{step.attempts}</span>
                </div>
                {step.error_message && (
                    <p className="mt-1 text-xs text-red-400">{step.error_message}</p>
                )}
                {step.output !== null && step.output !== undefined && (
                    <div className="mt-1">
                        <button className="text-xs text-[var(--muted-foreground)] underline" onClick={onToggleOutput}>
                            {outputOpen ? 'Hide' : 'Show'} output
                        </button>
                        {outputOpen && (
                            <pre className="mt-1 rounded bg-[var(--muted)] p-2 font-mono text-xs text-[var(--foreground)] overflow-auto max-h-32">
                                {JSON.stringify(step.output, null, 2)}
                            </pre>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
