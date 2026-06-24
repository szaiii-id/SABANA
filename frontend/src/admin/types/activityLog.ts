export interface ActivityLog {
  id: string;
  actor_type: 'admin' | 'citizen' | 'system';
  actor_name: string;
  actor_role: string | null;
  module: string;
  action: string;
  action_label: string;
  target_type: string | null;
  target_name: string | null;
  metadata: Record<string, unknown> | null;
  ip_address: string | null;
  created_at: string;
  source: string;
}

export interface ActivityLogFilters {
  module?: string;
  actor_name?: string;
  actor_role?: string;
  from?: string;
  to?: string;
  page?: number;
  per_page?: number;
}

export interface ActivityLogResponse {
  status: string;
  data: ActivityLog[];
  meta: {
    current_page: number;
    total: number;
    last_page: number;
    per_page: number;
  };
}