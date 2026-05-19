import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, useForm } from '@inertiajs/react';
import { SyntheticEvent } from 'react';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e: SyntheticEvent) => {
        e.preventDefault();
        post(route('password.confirm'), { onFinish: () => reset('password') });
    };

    return (
        <GuestLayout>
            <Head title="Confirmar senha" />

            <p className="text-muted-foreground mb-4 text-sm">
                Esta é uma área protegida. Confirme sua senha para continuar.
            </p>

            <form onSubmit={submit} className="space-y-4">
                <div className="space-y-2">
                    <Label htmlFor="password">Senha</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        autoFocus
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    {errors.password && (
                        <p className="text-destructive text-sm">
                            {errors.password}
                        </p>
                    )}
                </div>

                <div className="flex justify-end">
                    <Button type="submit" disabled={processing}>
                        Confirmar
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
