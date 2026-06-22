export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};

export type DocumentStatus = 'draft' | 'pending' | 'completed' | 'cancelled';

export interface DocumentData {
    id: number;
    title: string;
    description: string | null;
    status: DocumentStatus;
    fileOriginalName: string;
    createdAt: string;
    signatoryCount: number;
    signedCount: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}
