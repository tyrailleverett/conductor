export type JobStatus =
    | 'pending'
    | 'running'
    | 'completed'
    | 'failed'
    | 'cancellation_requested'
    | 'cancelled';

export type WorkflowStatus =
    | 'pending'
    | 'running'
    | 'waiting'
    | 'completed'
    | 'failed'
    | 'cancelled';

export interface ConductorJobLog {
    id: number;
    level: 'debug' | 'info' | 'warning' | 'error';
    message: string;
    logged_at: string;
}

export interface ConductorJob {
    id: string;
    class: string;
    display_name: string;
    status: JobStatus;
    queue: string | null;
    connection: string | null;
    tags: string[];
    attempts: number;
    max_attempts: number | null;
    is_cancellable: boolean;
    started_at: string | null;
    completed_at: string | null;
    failed_at: string | null;
    cancelled_at: string | null;
    duration_ms: number | null;
    error_message: string | null;
    stack_trace: string | null;
    logs?: ConductorJobLog[];
    created_at: string;
}

export interface ConductorWorkflowStep {
    id: number;
    name: string;
    step_index: number;
    status: 'pending' | 'running' | 'completed' | 'failed' | 'skipped';
    attempts: number;
    started_at: string | null;
    completed_at: string | null;
    duration_ms: number | null;
    error_message: string | null;
    output: unknown;
}

export interface ConductorWorkflow {
    id: string;
    class: string;
    display_name: string;
    status: WorkflowStatus;
    current_step_index: number;
    step_count: number;
    steps?: ConductorWorkflowStep[];
    created_at: string;
    completed_at: string | null;
    cancelled_at: string | null;
}

export interface ConductorEventRun {
    id: number;
    function_class: string;
    status: 'pending' | 'running' | 'completed' | 'failed';
    error_message: string | null;
    attempts: number;
    started_at: string | null;
    completed_at: string | null;
    duration_ms: number | null;
}

export interface ConductorEvent {
    id: string;
    name: string;
    payload: Record<string, unknown>;
    dispatched_at: string;
    runs_count: number;
    runs?: ConductorEventRun[];
}

export interface ConductorSchedule {
    id: number;
    function_class: string;
    display_name: string;
    cron_expression: string;
    is_active: boolean;
    last_run_at: string | null;
    next_run_at: string | null;
    last_run_status: 'completed' | 'failed' | null;
}

export interface ConductorWorker {
    id: string;
    worker_name: string;
    queue: string;
    connection: string;
    hostname: string;
    process_id: number;
    status: 'idle' | 'busy' | 'offline';
    current_job_uuid: string | null;
    last_heartbeat_at: string;
}

export interface MetricPoint {
    value: number;
    recorded_at: string;
}

export interface MetricsResponse {
    window: '1h' | '24h' | '7d';
    throughput: MetricPoint[];
    failure_rate: MetricPoint[];
    queue_depth: Record<string, MetricPoint[]>;
}

export interface PaginatedResponse<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    links: {
        first: string;
        last: string;
        prev: string | null;
        next: string | null;
    };
}
