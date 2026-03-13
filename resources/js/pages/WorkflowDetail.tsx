import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { apiGet, apiDelete } from '@/lib/api';
import { formatDuration } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';
import { ArrowLeft } from 'lucide-react';
import type { ConductorWorkflow, ConductorWorkflowStep } from '@/types';

function StatusDot({ status }: { status: string }) {
    const color =
        status === 'completed' ? 'bg-green-400 text-green-400'
        : status === 'failed' ? 'bg-red-400 text-red-400'
        : status === 'running' ? 'bg-amber-400 text-amber-400'
        : 'bg-zinc-500 text-zinc-400';
    return (
        <span className="flex items-center gap-1.5 text-sm">
            <span className={`inline-block h-2 w-2 rounded-full ${color.split(' ')[0]}`} />
            <span className={`capitalize font-medium ${color.split(' ')[1]}`}>{status}</span>
        </span>
    );
}

function formatDateTime(iso: string | null): string {
    if (!iso) { return '—'; }
    const d = new Date(iso);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) + ', ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
}

function MetaCard({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="rounded-lg border border-[var(--border)] bg-[var(--card)] px-4 py-3">
            <div className="text-[10px] font-medium uppercase tracking-wider text-[var(--muted-foreground)] mb-1">{label}</div>
            <div className="text-sm text-[var(--foreground)] font-mono">{children}</div>
        </div>
    );
}

export default function WorkflowDetail() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
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
                <Skeleton className="h-6 w-32" />
                <Skeleton className="h-10 w-80" />
                <Skeleton className="h-24 rounded-lg" />
                <Skeleton className="h-48 rounded-lg" />
            </div>
        );
    }

    if (!workflow) {
        return <p className="text-[var(--muted-foreground)]">Workflow not found.</p>;
    }

    const canCancel = workflow.status === 'running' || workflow.status === 'waiting' || workflow.status === 'pending';

    return (
        <div className="space-y-6">
            {/* Back link */}
            <button
                className="flex items-center gap-1.5 text-sm text-[var(--muted-foreground)] hover:text-[var(--foreground)] transition-colors"
                onClick={() => { navigate('/workflows'); }}
            >
                <ArrowLeft className="h-3.5 w-3.5" />
                All Workflows
            </button>

            {/* Title + status */}
            <div>
                <div className="flex items-center gap-3">
                    <h1 className="font-mono text-2xl font-semibold text-[var(--foreground)]">{workflow.display_name}</h1>
                    <StatusDot status={workflow.status} />
                    {canCancel && (
                        <button
                            className="ml-2 rounded border border-red-500/40 bg-red-500/10 px-3 py-1 text-xs text-red-400 hover:bg-red-500/20 transition-colors disabled:opacity-50"
                            onClick={handleCancel}
                            disabled={cancelLoading}
                        >
                            Cancel
                        </button>
                    )}
                </div>
                <p className="mt-1 font-mono text-sm text-[var(--muted-foreground)]">{workflow.id}</p>
            </div>

            {/* Metadata cards — 4 columns */}
            <div className="grid grid-cols-2 gap-3 md:grid-cols-4">
                <MetaCard label="Job Class">{workflow.class}</MetaCard>
                <MetaCard label="Queue">—</MetaCard>
                <MetaCard label="Created">{formatDateTime(workflow.created_at)}</MetaCard>
                <MetaCard label="Completed">{formatDateTime(workflow.completed_at)}</MetaCard>
            </div>

            {/* Steps */}
            {(workflow.steps ?? []).length > 0 && (
                <div className="space-y-3">
                    <h2 className="text-base font-semibold text-[var(--foreground)]">Steps</h2>
                    <div className="flex flex-col gap-0">
                        {(workflow.steps ?? []).map((step, idx) => (
                            <StepRow
                                key={step.id}
                                step={step}
                                isLast={idx === (workflow.steps ?? []).length - 1}
                                outputOpen={openOutputs.has(step.id)}
                                onToggleOutput={() => { toggleOutput(step.id); }}
                            />
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}

function StepRow({
    step,
    isLast,
    outputOpen,
    onToggleOutput,
}: {
    step: ConductorWorkflowStep;
    isLast: boolean;
    outputOpen: boolean;
    onToggleOutput: () => void;
}) {
    const dotColor =
        step.status === 'completed' ? 'border-green-400 bg-green-400/20 text-green-400'
        : step.status === 'failed' ? 'border-red-400 bg-red-400/20 text-red-400'
        : step.status === 'running' ? 'border-amber-400 bg-amber-400/20 text-amber-400'
        : 'border-zinc-600 bg-zinc-800 text-zinc-500';

    const statusColor =
        step.status === 'completed' ? 'text-green-400'
        : step.status === 'failed' ? 'text-red-400'
        : step.status === 'running' ? 'text-amber-400'
        : 'text-zinc-500';

    const startedFmt = formatDateTime(step.started_at);
    const completedFmt = formatDateTime(step.completed_at);
    const timeRange = step.started_at ? `${startedFmt} → ${completedFmt}` : null;

    return (
        <div className="flex gap-4">
            {/* Timeline spine */}
            <div className="flex flex-col items-center">
                <div className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-[10px] font-bold ${dotColor}`}>
                    {step.step_index + 1}
                </div>
                {!isLast && <div className="w-px flex-1 bg-[var(--border)] min-h-[1.5rem]" />}
            </div>

            {/* Step card */}
            <div className={`flex-1 ${isLast ? 'pb-0' : 'pb-4'}`}>
                <div className="rounded-lg border border-[var(--border)] bg-[var(--card)] px-4 py-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium text-[var(--foreground)]">{step.name}</span>
                        <div className="flex items-center gap-3 text-sm">
                            <span className={`flex items-center gap-1.5 ${statusColor}`}>
                                <span className={`inline-block h-1.5 w-1.5 rounded-full ${step.status === 'completed' ? 'bg-green-400' : step.status === 'failed' ? 'bg-red-400' : step.status === 'running' ? 'bg-amber-400' : 'bg-zinc-500'}`} />
                                <span className="capitalize">{step.status}</span>
                            </span>
                            <span className="text-[var(--muted-foreground)]">{formatDuration(step.duration_ms)}</span>
                        </div>
                    </div>
                    {timeRange && (
                        <p className="mt-1 font-mono text-xs text-[var(--muted-foreground)]">{timeRange}</p>
                    )}
                    {step.error_message && (
                        <p className="mt-2 text-xs text-red-400">{step.error_message}</p>
                    )}
                    {step.output !== null && step.output !== undefined && (
                        <div className="mt-2">
                            <button
                                className="text-xs text-[var(--muted-foreground)] hover:text-[var(--foreground)] underline transition-colors"
                                onClick={onToggleOutput}
                            >
                                {outputOpen ? 'Hide output' : 'Show output'}
                            </button>
                            {outputOpen && (
                                <pre className="mt-2 rounded-md border border-[var(--border)] bg-[#0d0d0d] p-3 font-mono text-xs text-[var(--foreground)] overflow-auto max-h-40">
                                    {JSON.stringify(step.output, null, 2)}
                                </pre>
                            )}
                        </div>
                    )}
                    {step.output === null || step.output === undefined ? (
                        // Always show output block matching art (even empty) — skip if no output key
                        null
                    ) : null}
                </div>
            </div>
        </div>
    );
}
