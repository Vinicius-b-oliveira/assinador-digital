import DocumentStatusBadge from '@/components/DocumentStatusBadge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { DocumentData, PageProps } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, Pencil, Trash2 } from 'lucide-react';

type ShowProps = PageProps<{
    document: DocumentData;
    fileUrl: string;
}>;

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('pt-BR');
}

export default function Show({ document, fileUrl }: ShowProps) {
    const isDraft = document.status === 'draft';

    const destroy = () => {
        router.delete(route('documents.destroy', document.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-foreground truncate text-xl leading-tight font-semibold">
                        {document.title}
                    </h2>
                    <DocumentStatusBadge status={document.status} />
                </div>
            }
        >
            <Head title={document.title} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <Button asChild variant="ghost" size="sm">
                            <Link href={route('documents.index')}>
                                <ArrowLeft className="h-4 w-4" />
                                Voltar
                            </Link>
                        </Button>

                        <div className="flex items-center gap-2">
                            <Button asChild variant="outline" size="sm">
                                <a href={fileUrl} download>
                                    <Download className="h-4 w-4" />
                                    Baixar
                                </a>
                            </Button>

                            {isDraft && (
                                <Button asChild variant="outline" size="sm">
                                    <Link
                                        href={route(
                                            'documents.edit',
                                            document.id,
                                        )}
                                    >
                                        <Pencil className="h-4 w-4" />
                                        Editar
                                    </Link>
                                </Button>
                            )}

                            {isDraft && (
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button variant="destructive" size="sm">
                                            <Trash2 className="h-4 w-4" />
                                            Excluir
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Excluir documento
                                            </DialogTitle>
                                            <DialogDescription>
                                                Tem certeza que deseja excluir “
                                                {document.title}”? Esta ação
                                                pode ser desfeita por um
                                                administrador.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="outline">
                                                    Cancelar
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                variant="destructive"
                                                onClick={destroy}
                                            >
                                                Excluir
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            )}
                        </div>
                    </div>

                    <div className="border-border bg-card text-card-foreground space-y-2 rounded-lg border p-6 shadow-xs">
                        {document.description && (
                            <p className="text-foreground">
                                {document.description}
                            </p>
                        )}
                        <p className="text-muted-foreground text-sm">
                            Enviado em {formatDate(document.createdAt)} ·{' '}
                            {document.signedCount}/{document.signatoryCount}{' '}
                            assinaram
                        </p>
                    </div>

                    <div className="border-border bg-card overflow-hidden rounded-lg border shadow-xs">
                        <iframe
                            src={fileUrl}
                            title={document.title}
                            className="h-[80vh] w-full"
                        />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
