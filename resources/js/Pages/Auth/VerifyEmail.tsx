import { Button } from '@/components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { SyntheticEvent } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Verificar e-mail" />

            <p className="text-muted-foreground mb-4 text-sm">
                Obrigado por se cadastrar. Antes de continuar, verifique seu
                e-mail clicando no link que acabamos de enviar. Se não recebeu,
                podemos reenviar.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-emerald-600">
                    Um novo link de verificação foi enviado para o e-mail
                    cadastrado.
                </div>
            )}

            <form onSubmit={submit}>
                <div className="flex items-center justify-between">
                    <Button type="submit" disabled={processing}>
                        Reenviar e-mail
                    </Button>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-muted-foreground hover:text-foreground text-sm underline"
                    >
                        Sair
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
