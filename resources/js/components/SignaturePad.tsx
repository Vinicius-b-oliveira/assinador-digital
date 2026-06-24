import { Button } from '@/components/ui/button';
import { Eraser } from 'lucide-react';
import { useEffect, useRef } from 'react';
import SignaturePadLib from 'signature_pad';

type SignaturePadProps = {
    onChange: (dataUrl: string) => void;
    onClear?: () => void;
};

const CANVAS_HEIGHT = 192;

export default function SignaturePad({ onChange, onClear }: SignaturePadProps) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const padRef = useRef<SignaturePadLib | null>(null);
    const onChangeRef = useRef(onChange);
    onChangeRef.current = onChange;

    useEffect(() => {
        const canvas = canvasRef.current;

        if (!canvas) {
            return;
        }

        const pad = new SignaturePadLib(canvas, {
            penColor: '#111827',
            backgroundColor: 'rgb(255, 255, 255)',
        });
        padRef.current = pad;

        const resize = () => {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const { width } = canvas.getBoundingClientRect();

            canvas.width = width * ratio;
            canvas.height = CANVAS_HEIGHT * ratio;
            canvas.getContext('2d')?.scale(ratio, ratio);
            pad.clear();
        };

        const handleEnd = () => onChangeRef.current(pad.toDataURL('image/png'));

        resize();
        window.addEventListener('resize', resize);
        pad.addEventListener('endStroke', handleEnd);

        return () => {
            window.removeEventListener('resize', resize);
            pad.removeEventListener('endStroke', handleEnd);
            pad.off();
        };
    }, []);

    const clear = () => {
        padRef.current?.clear();
        onClear?.();
    };

    return (
        <div className="space-y-2">
            <div className="border-border overflow-hidden rounded-md border bg-white">
                <canvas
                    ref={canvasRef}
                    className="w-full touch-none"
                    style={{ height: CANVAS_HEIGHT }}
                />
            </div>
            <Button type="button" variant="outline" size="sm" onClick={clear}>
                <Eraser className="h-4 w-4" />
                Limpar
            </Button>
        </div>
    );
}
