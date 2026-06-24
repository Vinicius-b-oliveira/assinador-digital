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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ActivityData,
    DocumentData,
    PageProps,
    SignatoryData,
    SignatoryStatus,
} from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    BellRing,
    CheckCircle2,
    Clock3,
    Download,
    FileText,
    Mail,
    Pencil,
    Send,
    Trash2,
    UserPlus,
    XCircle,
    type LucideIcon,
} from 'lucide-react';
import { type SyntheticEvent } from 'react';

type ShowProps = PageProps<{
    document: DocumentData;
    signatories: SignatoryData[];
    activities: ActivityData[];
    fileUrl: string;
}>;

type SignatoryForm = {
    name: string;
    email: string;
};

const signatoryStatusConfig: Record<
    SignatoryStatus,
    { label: string; className: string; icon: LucideIcon }
> = {
    pending: {
        label: 'Pendente',
        className:
            'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        icon: Clock3,
    },
    signed: {
        label: 'Assinado',
        className:
            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        icon: CheckCircle2,
    },
    declined: {
        label: 'Recusado',
        className: 'bg-destructive/10 text-destructive',
        icon: XCircle,
    },
};

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('pt-BR');
}

function formatDateTime(iso: string): string {
    return new Date(iso).toLocaleString('pt-BR');
}

const activityIconConfig: Record<
    string,
    { icon: LucideIcon; className: string }
> = {
    created: { icon: FileText, className: 'text-muted-foreground' },
    sent: { icon: Send, className: 'text-blue-600 dark:text-blue-400' },
    signed: {
        icon: CheckCircle2,
        className: 'text-emerald-600 dark:text-emerald-400',
    },
    completed: {
        icon: CheckCircle2,
        className: 'text-emerald-600 dark:text-emerald-400',
    },
    declined: { icon: XCircle, className: 'text-destructive' },
};

function ActivityIcon({ event }: { event: string | null }) {
    const { icon: Icon, className } = activityIconConfig[event ?? ''] ?? {
        icon: Clock3,
        className: 'text-muted-foreground',
    };

    return <Icon className={`h-4 w-4 shrink-0 ${className}`} />;
}

function SignatoryStatusBadge({ status }: { status: SignatoryStatus }) {
    const { label, className, icon: Icon } = signatoryStatusConfig[status];

    return (
        <span
            className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ${className}`}
        >
            <Icon className="h-3 w-3" />
            {label}
        </span>
    );
}

function EditSignatoryDialog({ signatory }: { signatory: SignatoryData }) {
    const form = useForm<SignatoryForm>({
        name: signatory.name,
        email: signatory.email,
    });

    const submit = (event: SyntheticEvent) => {
        event.preventDefault();
        form.put(route('signatories.update', signatory.id), {
            preserveScroll: true,
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon-sm" title="Editar">
                    <Pencil className="h-4 w-4" />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Editar signatário</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor={`edit-name-${signatory.id}`}>
                            Nome
                        </Label>
                        <Input
                            id={`edit-name-${signatory.id}`}
                            value={form.data.name}
                            onChange={(event) =>
                                form.setData('name', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.name)}
                        />
                        {form.errors.name && (
                            <p className="text-destructive text-sm">
                                {form.errors.name}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor={`edit-email-${signatory.id}`}>
                            E-mail
                        </Label>
                        <Input
                            id={`edit-email-${signatory.id}`}
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.email)}
                        />
                        {form.errors.email && (
                            <p className="text-destructive text-sm">
                                {form.errors.email}
                            </p>
                        )}
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={form.processing}>
                            Salvar
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteSignatoryDialog({ signatory }: { signatory: SignatoryData }) {
    const destroy = () => {
        router.delete(route('signatories.destroy', signatory.id), {
            preserveScroll: true,
        });
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="ghost" size="icon-sm" title="Remover">
                    <Trash2 className="h-4 w-4" />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Remover signatário</DialogTitle>
                    <DialogDescription>
                        Remover {signatory.name} do fluxo de assinatura.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button variant="outline">Cancelar</Button>
                    </DialogClose>
                    <Button variant="destructive" onClick={destroy}>
                        Remover
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

export default function Show({
    document,
    signatories,
    activities,
    fileUrl,
}: ShowProps) {
    const isDraft = document.status === 'draft';
    const isPending = document.status === 'pending';
    const hasSignatories = signatories.length > 0;
    const nextPending = signatories.find(
        (signatory) => signatory.status === 'pending',
    );
    const form = useForm<SignatoryForm>({ name: '', email: '' });

    const destroy = () => {
        router.delete(route('documents.destroy', document.id));
    };

    const send = () => {
        router.post(route('documents.send', document.id), undefined, {
            preserveScroll: true,
        });
    };

    const remind = (signatoryId: number) => {
        router.post(route('signatories.remind', signatoryId), undefined, {
            preserveScroll: true,
        });
    };

    const addSignatory = (event: SyntheticEvent) => {
        event.preventDefault();
        form.post(route('documents.signatories.store', document.id), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const moveSignatory = (index: number, direction: -1 | 1) => {
        const next = [...signatories];
        const target = index + direction;

        [next[index], next[target]] = [next[target], next[index]];

        router.put(
            route('documents.signatories.reorder', document.id),
            { signatories: next.map((signatory) => signatory.id) },
            { preserveScroll: true },
        );
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
                                <Dialog>
                                    <DialogTrigger asChild>
                                        <Button
                                            size="sm"
                                            disabled={!hasSignatories}
                                            title={
                                                hasSignatories
                                                    ? undefined
                                                    : 'Adicione ao menos um signatário'
                                            }
                                        >
                                            <Send className="h-4 w-4" />
                                            Enviar para assinatura
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Enviar para assinatura
                                            </DialogTitle>
                                            <DialogDescription>
                                                O primeiro signatário receberá o
                                                convite por e-mail. Após o
                                                envio, o documento não poderá
                                                mais ser editado.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="outline">
                                                    Cancelar
                                                </Button>
                                            </DialogClose>
                                            <Button onClick={send}>
                                                Enviar
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            )}

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
                        {document.status === 'pending' && nextPending && (
                            <p className="flex items-center gap-1.5 text-sm font-medium text-amber-700 dark:text-amber-300">
                                <Clock3 className="h-3.5 w-3.5" />
                                Aguardando assinatura de {nextPending.name}
                            </p>
                        )}
                    </div>

                    <section className="border-border bg-card text-card-foreground rounded-lg border p-6 shadow-xs">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 className="text-lg font-semibold">
                                    Signatários
                                </h3>
                                <p className="text-muted-foreground text-sm">
                                    {document.signatoryCount} no fluxo ·{' '}
                                    {document.signedCount} assinaram
                                </p>
                            </div>

                            {isDraft && (
                                <form
                                    onSubmit={addSignatory}
                                    className="grid gap-3 sm:min-w-[28rem] sm:grid-cols-[1fr_1fr_auto]"
                                >
                                    <div className="space-y-1">
                                        <Label htmlFor="signatory-name">
                                            Nome
                                        </Label>
                                        <Input
                                            id="signatory-name"
                                            value={form.data.name}
                                            onChange={(event) =>
                                                form.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.name,
                                            )}
                                        />
                                        {form.errors.name && (
                                            <p className="text-destructive text-sm">
                                                {form.errors.name}
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-1">
                                        <Label htmlFor="signatory-email">
                                            E-mail
                                        </Label>
                                        <Input
                                            id="signatory-email"
                                            type="email"
                                            value={form.data.email}
                                            onChange={(event) =>
                                                form.setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={Boolean(
                                                form.errors.email,
                                            )}
                                        />
                                        {form.errors.email && (
                                            <p className="text-destructive text-sm">
                                                {form.errors.email}
                                            </p>
                                        )}
                                    </div>

                                    <Button
                                        type="submit"
                                        className="self-end"
                                        disabled={form.processing}
                                    >
                                        <UserPlus className="h-4 w-4" />
                                        Adicionar
                                    </Button>
                                </form>
                            )}
                        </div>

                        <div className="mt-5 divide-y">
                            {signatories.length === 0 && (
                                <div className="text-muted-foreground rounded-md border border-dashed p-6 text-center text-sm">
                                    Nenhum signatário adicionado.
                                </div>
                            )}

                            {signatories.map((signatory, index) => (
                                <div
                                    key={signatory.id}
                                    className="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="bg-muted text-muted-foreground inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-medium">
                                                {signatory.order}
                                            </span>
                                            <p className="truncate font-medium">
                                                {signatory.name}
                                            </p>
                                            <SignatoryStatusBadge
                                                status={signatory.status}
                                            />
                                        </div>
                                        <p className="text-muted-foreground mt-1 flex items-center gap-1 text-sm break-all">
                                            <Mail className="h-3.5 w-3.5" />
                                            {signatory.email}
                                        </p>
                                    </div>

                                    {isDraft && (
                                        <div className="flex items-center gap-1 self-start sm:self-auto">
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                title="Subir"
                                                disabled={index === 0}
                                                onClick={() =>
                                                    moveSignatory(index, -1)
                                                }
                                            >
                                                <ArrowUp className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                title="Descer"
                                                disabled={
                                                    index ===
                                                    signatories.length - 1
                                                }
                                                onClick={() =>
                                                    moveSignatory(index, 1)
                                                }
                                            >
                                                <ArrowDown className="h-4 w-4" />
                                            </Button>
                                            <EditSignatoryDialog
                                                signatory={signatory}
                                            />
                                            <DeleteSignatoryDialog
                                                signatory={signatory}
                                            />
                                        </div>
                                    )}

                                    {isPending &&
                                        nextPending?.id === signatory.id && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                className="self-start sm:self-auto"
                                                onClick={() =>
                                                    remind(signatory.id)
                                                }
                                            >
                                                <BellRing className="h-4 w-4" />
                                                Reenviar convite
                                            </Button>
                                        )}
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="border-border bg-card text-card-foreground rounded-lg border p-6 shadow-xs">
                        <h3 className="text-lg font-semibold">Histórico</h3>

                        {activities.length === 0 ? (
                            <p className="text-muted-foreground mt-2 text-sm">
                                Nenhum evento registrado ainda.
                            </p>
                        ) : (
                            <ol className="mt-4 space-y-4">
                                {activities.map((activity) => (
                                    <li
                                        key={activity.id}
                                        className="flex items-start gap-3"
                                    >
                                        <ActivityIcon event={activity.event} />
                                        <div className="min-w-0">
                                            <p className="text-sm font-medium">
                                                {activity.description}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {formatDateTime(
                                                    activity.createdAt,
                                                )}
                                                {activity.causer &&
                                                    ` · ${activity.causer}`}
                                                {activity.signatory &&
                                                    ` · ${activity.signatory}`}
                                                {activity.ip &&
                                                    ` · IP ${activity.ip}`}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        )}
                    </section>

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
