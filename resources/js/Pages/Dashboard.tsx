import DocumentStatusBadge from '@/components/DocumentStatusBadge';
import { Button } from '@/components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DashboardStats, PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    FilePen,
    FileText,
    PenLine,
    Plus,
} from 'lucide-react';
import { ComponentType } from 'react';

type DashboardProps = PageProps<{
    stats: DashboardStats;
}>;

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('pt-BR');
}

function StatCard({
    label,
    value,
    icon: Icon,
    className,
}: {
    label: string;
    value: number;
    icon: ComponentType<{ className?: string }>;
    className: string;
}) {
    return (
        <div className="border-border bg-card text-card-foreground flex items-center gap-4 rounded-lg border p-5 shadow-xs">
            <span
                className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-full ${className}`}
            >
                <Icon className="h-5 w-5" />
            </span>
            <div className="min-w-0">
                <p className="text-2xl leading-none font-semibold">{value}</p>
                <p className="text-muted-foreground truncate text-sm">
                    {label}
                </p>
            </div>
        </div>
    );
}

export default function Dashboard({ stats }: DashboardProps) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-foreground text-xl leading-tight font-semibold">
                        Dashboard
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
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <StatCard
                            label="Documentos no total"
                            value={stats.total}
                            icon={FileText}
                            className="bg-muted text-muted-foreground"
                        />
                        <StatCard
                            label="Aguardando assinatura"
                            value={stats.pending}
                            icon={Clock}
                            className="bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                        />
                        <StatCard
                            label="Concluídos"
                            value={stats.completed}
                            icon={CheckCircle2}
                            className="bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                        />
                        <StatCard
                            label="Rascunhos"
                            value={stats.draft}
                            icon={FilePen}
                            className="bg-muted text-muted-foreground"
                        />
                    </div>

                    <div className="border-border bg-card text-card-foreground flex flex-wrap items-center justify-between gap-4 rounded-lg border p-5 shadow-xs">
                        <div className="flex items-center gap-3">
                            <span className="bg-primary/10 text-primary flex h-11 w-11 shrink-0 items-center justify-center rounded-full">
                                <PenLine className="h-5 w-5" />
                            </span>
                            <div>
                                <p className="text-2xl leading-none font-semibold">
                                    {stats.signaturesCollected}
                                </p>
                                <p className="text-muted-foreground text-sm">
                                    assinaturas coletadas
                                </p>
                            </div>
                        </div>
                        <div className="min-w-48 flex-1">
                            <div className="text-muted-foreground mb-1 flex items-center justify-between text-sm">
                                <span>Taxa de conclusão</span>
                                <span className="text-foreground font-medium">
                                    {stats.completionRate}%
                                </span>
                            </div>
                            <div className="bg-muted h-2 overflow-hidden rounded-full">
                                <div
                                    className="bg-primary h-full rounded-full transition-all"
                                    style={{
                                        width: `${stats.completionRate}%`,
                                    }}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="border-border bg-card text-card-foreground overflow-hidden rounded-lg border shadow-xs">
                        <div className="border-border flex items-center justify-between border-b px-6 py-4">
                            <h3 className="font-medium">Documentos recentes</h3>
                            <Link
                                href={route('documents.index')}
                                className="text-muted-foreground hover:text-foreground text-sm transition-colors"
                            >
                                Ver todos
                            </Link>
                        </div>

                        {stats.recentDocuments.length === 0 ? (
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
                                {stats.recentDocuments.map((document) => (
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
