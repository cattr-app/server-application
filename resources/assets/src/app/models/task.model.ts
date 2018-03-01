import {Item} from "./item.model";

export class Task extends Item {
    public id: number;
    public project_id?: number;
    public task_name?: string;
    public active?: number;
    public user_id?: number;
    public assigned_by?: number;
    public url?: string;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;

    constructor(id?, projectId?, taskName?, active = 1, userId?, assignedBy?, url = "URL", createdAt?, updatedAt?, deletedAt?) {
        super();
        this.id = id;
        this.project_id = projectId;
        this.task_name = taskName;
        this.active = active;
        this.user_id = userId;
        this.assigned_by = assignedBy;
        this.url = url;
        this.deleted_at = deletedAt;
        this.created_at = createdAt;
        this.updated_at = updatedAt;
    }
}
