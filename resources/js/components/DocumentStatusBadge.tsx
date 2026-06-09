import { DocumentStatus } from '@/types';

const config: Record<DocumentStatus, { label: string; className: string }> = {
    draft: {
        label: 'Rascunho',
        className: 'bg-muted text-muted-foreground',
    },
    pending: {
        label: 'Aguardando assinatura',
        className:
            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
    },
    completed: {
        label: 'Concluído',
        className:
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
    },
    cancelled: {
        label: 'Cancelado',
        className: 'bg-destructive/10 text-destructive',
    },
};

export const documentStatusLabels: Record<DocumentStatus, string> =
    Object.fromEntries(
        Object.entries(config).map(([status, { label }]) => [status, label]),
    ) as Record<DocumentStatus, string>;

export default function DocumentStatusBadge({
    status,
}: {
    status: DocumentStatus;
}) {
    const { label, className } = config[status];

    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${className}`}
        >
            {label}
        </span>
    );
}
