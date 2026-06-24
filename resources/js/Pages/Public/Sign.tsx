import ApplicationLogo from '@/components/ApplicationLogo';
import SignaturePad from '@/components/SignaturePad';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { Head, router, useForm } from '@inertiajs/react';
import {
    Ban,
    CheckCircle2,
    Clock3,
    XCircle,
    type LucideIcon,
} from 'lucide-react';
import { type FormEvent } from 'react';

type SignState =
    | 'ready'
    | 'waiting'
    | 'signed'
    | 'declined'
    | 'completed'
    | 'cancelled';

type SignProps = {
    documentTitle: string;
    signatory: { name: string; email: string };
    token: string;
    fileUrl: string;
    state: SignState;
};

type SignForm = {
    signature_data: string;
    signer_name: string;
    accept_terms: boolean;
};

const statusScreens: Record<
    Exclude<SignState, 'ready'>,
    { title: string; description: string; icon: LucideIcon; className: string }
> = {
    waiting: {
        title: 'Aguardando a sua vez',
        description:
            'Ainda não é a sua vez de assinar. Você receberá um e-mail quando o documento estiver disponível para você.',
        icon: Clock3,
        className: 'text-amber-600 dark:text-amber-400',
    },
    signed: {
        title: 'Assinatura registrada',
        description: 'Você já assinou este documento. Obrigado!',
        icon: CheckCircle2,
        className: 'text-emerald-600 dark:text-emerald-400',
    },
    completed: {
        title: 'Documento concluído',
        description: 'Todos os signatários já assinaram este documento.',
        icon: CheckCircle2,
        className: 'text-emerald-600 dark:text-emerald-400',
    },
    declined: {
        title: 'Assinatura recusada',
        description: 'Você recusou a assinatura deste documento.',
        icon: XCircle,
        className: 'text-destructive',
    },
    cancelled: {
        title: 'Documento cancelado',
        description:
            'Este documento foi cancelado e não está mais disponível para assinatura.',
        icon: Ban,
        className: 'text-muted-foreground',
    },
};

function PublicShell({ children }: { children: React.ReactNode }) {
    return (
        <div className="bg-background min-h-screen">
            <header className="border-border border-b">
                <div className="mx-auto flex max-w-6xl items-center gap-3 px-4 py-4 sm:px-6 lg:px-8">
                    <ApplicationLogo className="text-muted-foreground h-8 w-8 fill-current" />
                    <span className="font-semibold">Assinador Digital</span>
                </div>
            </header>
            <main className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}

function StatusScreen({
    state,
    documentTitle,
}: {
    state: Exclude<SignState, 'ready'>;
    documentTitle: string;
}) {
    const { title, description, icon: Icon, className } = statusScreens[state];

    return (
        <div className="border-border bg-card text-card-foreground mx-auto max-w-md rounded-lg border p-8 text-center shadow-xs">
            <Icon className={`mx-auto h-12 w-12 ${className}`} />
            <h1 className="mt-4 text-xl font-semibold">{title}</h1>
            <p className="text-muted-foreground mt-2 text-sm">{description}</p>
            <p className="text-muted-foreground mt-4 text-sm">
                Documento: <span className="font-medium">{documentTitle}</span>
            </p>
        </div>
    );
}

export default function Sign({
    documentTitle,
    signatory,
    token,
    fileUrl,
    state,
}: SignProps) {
    const form = useForm<SignForm>({
        signature_data: '',
        signer_name: signatory.name,
        accept_terms: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('public.sign.sign', token), { preserveScroll: true });
    };

    const decline = () => {
        router.post(route('public.sign.decline', token));
    };

    if (state !== 'ready') {
        return (
            <PublicShell>
                <Head title={`Assinar — ${documentTitle}`} />
                <StatusScreen state={state} documentTitle={documentTitle} />
            </PublicShell>
        );
    }

    return (
        <PublicShell>
            <Head title={`Assinar — ${documentTitle}`} />

            <div className="mb-6">
                <h1 className="text-2xl font-semibold">{documentTitle}</h1>
                <p className="text-muted-foreground text-sm">
                    Revise o documento abaixo e registre a sua assinatura.
                </p>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="border-border bg-card overflow-hidden rounded-lg border shadow-xs lg:col-span-2">
                    <iframe
                        src={fileUrl}
                        title={documentTitle}
                        className="h-[70vh] w-full"
                    />
                </div>

                <form
                    onSubmit={submit}
                    className="border-border bg-card text-card-foreground h-fit space-y-4 rounded-lg border p-6 shadow-xs"
                >
                    <h2 className="text-lg font-semibold">Sua assinatura</h2>

                    <div className="space-y-2">
                        <Label htmlFor="signer_name">Nome completo</Label>
                        <Input
                            id="signer_name"
                            value={form.data.signer_name}
                            onChange={(event) =>
                                form.setData('signer_name', event.target.value)
                            }
                            aria-invalid={Boolean(form.errors.signer_name)}
                        />
                        {form.errors.signer_name && (
                            <p className="text-destructive text-sm">
                                {form.errors.signer_name}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Desenhe a sua assinatura</Label>
                        <SignaturePad
                            onChange={(dataUrl) =>
                                form.setData('signature_data', dataUrl)
                            }
                            onClear={() => form.setData('signature_data', '')}
                        />
                        {form.errors.signature_data && (
                            <p className="text-destructive text-sm">
                                {form.errors.signature_data}
                            </p>
                        )}
                    </div>

                    <label className="flex items-start gap-2">
                        <Checkbox
                            checked={form.data.accept_terms}
                            onCheckedChange={(checked) =>
                                form.setData('accept_terms', checked === true)
                            }
                            aria-invalid={Boolean(form.errors.accept_terms)}
                        />
                        <span className="text-muted-foreground text-sm">
                            Declaro que li o documento e concordo em assiná-lo
                            eletronicamente.
                        </span>
                    </label>
                    {form.errors.accept_terms && (
                        <p className="text-destructive text-sm">
                            {form.errors.accept_terms}
                        </p>
                    )}

                    <div className="space-y-2 pt-2">
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            Assinar documento
                        </Button>

                        <Dialog>
                            <DialogTrigger asChild>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="text-destructive hover:text-destructive w-full"
                                >
                                    Recusar
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>
                                        Recusar assinatura
                                    </DialogTitle>
                                    <DialogDescription>
                                        Ao recusar, o documento será cancelado e
                                        não poderá mais ser assinado. Esta ação
                                        não pode ser desfeita.
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button variant="outline">
                                            Voltar
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        variant="destructive"
                                        onClick={decline}
                                    >
                                        Recusar
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </form>
            </div>
        </PublicShell>
    );
}
