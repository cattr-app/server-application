
export class Project {
    public id: number;
    public company_id?: string;
    public name?: string;
    public description?: string;
    public deleted_at?: string;
    public created_at?: string;
    public updated_at?: string;

    constructor(id?, companyId?, name?, description?, deletedAt?, createdAt?, updatedAt?) {
        this.id = id;
        this.company_id = companyId;
        this.name = name;
        this.description = description;
        this.deleted_at = deletedAt;
        this.created_at = createdAt;
        this.updated_at = updatedAt;
    }
}
