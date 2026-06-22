import DocumentStatusBadge, {
    documentStatusLabels,
} from '@/components/DocumentStatusBadge';
import { Button } from '@/components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DocumentData, DocumentStatus, PageProps, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileText, Plus } from 'lucide-react';

type IndexProps = PageProps<{
    documents: Paginated<DocumentData>;
    filters: { status: DocumentStatus | null };
    statuses: DocumentStatus[];
}>;

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('pt-BR');
}

export default function Index({ documents, filters, statuses }: IndexProps) {
    const applyFilter = (status: DocumentStatus | null) => {
        router.get(route('documents.index'), status ? { status } : {}, {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-foreground text-xl leading-tight font-semibold">
                        Documentos
                    </h2>
                    <Button asChild>
                        <Link href={route('documents.create')}>
                            <Plus className="h-4 w-4" />
                            Novo documento
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Documentos" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant={filters.status ? 'outline' : 'secondary'}
                            size="sm"
                            onClick={() => applyFilter(null)}
                        >
                            Todos
                        </Button>
                        {statuses.map((status) => (
                            <Button
                                key={status}
                                variant={
                                    filters.status === status
                                        ? 'secondary'
                                        : 'outline'
                                }
                                size="sm"
                                onClick={() => applyFilter(status)}
                            >
                                {documentStatusLabels[status]}
                            </Button>
                        ))}
                    </div>

                    <div className="border-border bg-card text-card-foreground overflow-hidden rounded-lg border shadow-xs">
                        {documents.data.length === 0 ? (
                            <div className="flex flex-col items-center gap-3 p-12 text-center">
                                <FileText className="text-muted-foreground h-10 w-10" />
                                <p className="text-muted-foreground text-sm">
                                    Nenhum documento por aqui ainda.
                                </p>
                                <Button asChild variant="outline" size="sm">
                                    <Link href={route('documents.create')}>
                                        Enviar o primeiro documento
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <ul className="divide-border divide-y">
                                {documents.data.map((document) => (
                                    <li key={document.id}>
                                        <Link
                                            href={route(
                                                'documents.show',
                                                document.id,
                                            )}
                                            className="hover:bg-accent flex items-center justify-between gap-4 px-6 py-4 transition-colors"
                                        >
                                            <div className="min-w-0">
                                                <p className="text-foreground truncate font-medium">
                                                    {document.title}
                                                </p>
                                                <p className="text-muted-foreground truncate text-sm">
                                                    {document.fileOriginalName}{' '}
                                                    ·{' '}
                                                    {formatDate(
                                                        document.createdAt,
                                                    )}
                                                </p>
                                            </div>
                                            <div className="flex shrink-0 items-center gap-4">
                                                <span className="text-muted-foreground hidden text-sm sm:inline">
                                                    {document.signedCount}/
                                                    {document.signatoryCount}{' '}
                                                    assinaram
                                                </span>
                                                <DocumentStatusBadge
                                                    status={document.status}
                                                />
                                            </div>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {documents.last_page > 1 && (
                        <div className="flex flex-wrap justify-center gap-1">
                            {documents.links.map((link, index) => (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'secondary' : 'ghost'
                                    }
                                    size="sm"
                                    disabled={!link.url}
                                    onClick={() =>
                                        link.url &&
                                        router.get(
                                            link.url,
                                            {},
                                            {
                                                preserveScroll: true,
                                                preserveState: true,
                                            },
                                        )
                                    }
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
