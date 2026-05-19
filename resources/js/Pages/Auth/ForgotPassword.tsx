import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { SyntheticEvent } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({ email: '' });

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Recuperar senha" />

            <p className="text-muted-foreground mb-4 text-sm">
                Informe seu e-mail e enviaremos um link para você cadastrar uma
                nova senha.
            </p>

            {status && (
                <div className="mb-4 text-sm font-medium text-emerald-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="email">E-mail</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        autoFocus
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    {errors.email && (
                        <p className="text-destructive text-sm">
                            {errors.email}
                        </p>
                    )}
                </div>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        Enviar link
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
