import ApplicationLogo from '@/components/ApplicationLogo';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="bg-background flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="text-muted-foreground h-20 w-20 fill-current" />
                </Link>
            </div>

            <div className="border-border bg-card text-card-foreground mt-6 w-full overflow-hidden rounded-lg border px-6 py-4 shadow-sm sm:max-w-md">
                {children}
            </div>
        </div>
    );
}
