import React, { useRef, useState, useCallback, useEffect } from 'react'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
    DialogDescription,
} from '@/components/ui/dialog'
import { CameraIcon, UploadIcon, XIcon, RefreshCwIcon, CheckIcon } from 'lucide-react'
import { cn } from '@/lib/utils'

type PatientAvatarProps = {
    initials?: string
    onCapture?: (file: File) => void
    className?: string
}

type ModalStep = 'live' | 'confirm'

export default function PatientAvatar({ initials = 'CN', onCapture, className }: PatientAvatarProps) {
    const [preview, setPreview]         = useState<string | null>(null)
    const [modalOpen, setModalOpen]     = useState(false)
    const [modalStep, setModalStep]     = useState<ModalStep>('live')
    const [captured, setCaptured]       = useState<string | null>(null)
    const [capturedFile, setCapturedFile] = useState<File | null>(null)
    const [cameraError, setCameraError] = useState<string | null>(null)
    const [stream, setStream]           = useState<MediaStream | null>(null)

    const videoRef     = useRef<HTMLVideoElement>(null)
    const canvasRef    = useRef<HTMLCanvasElement>(null)
    const fileInputRef = useRef<HTMLInputElement>(null)

    // ── Start camera ───────────────────────────────────────────────────────────
    const startCamera = useCallback(async () => {
        setCameraError(null)
        try {
            const mediaStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 480 },
                audio: false,
            })
            setStream(mediaStream)
            setTimeout(() => {
                if (videoRef.current) {
                    videoRef.current.srcObject = mediaStream
                    videoRef.current.play()
                }
            }, 80)
        } catch (err: any) {
            if (err.name === 'NotAllowedError') {
                setCameraError('Camera permission denied. Please allow access and try again.')
            } else if (err.name === 'NotFoundError') {
                setCameraError('No camera found on this device.')
            } else {
                setCameraError('Unable to access camera.')
            }
        }
    }, [])

    // ── Stop camera ────────────────────────────────────────────────────────────
    const stopCamera = useCallback((currentStream?: MediaStream | null) => {
        const s = currentStream ?? stream
        s?.getTracks().forEach(t => t.stop())
        setStream(null)
    }, [stream])

    // Open modal and start camera
    const openCameraModal = useCallback(async () => {
        setCaptured(null)
        setCapturedFile(null)
        setModalStep('live')
        setModalOpen(true)
        // slight delay so Dialog mounts before camera attaches
        setTimeout(() => startCamera(), 100)
    }, [startCamera])

    // ── Capture snapshot ───────────────────────────────────────────────────────
    const capturePhoto = useCallback(() => {
        const video  = videoRef.current
        const canvas = canvasRef.current
        if (!video || !canvas) return

        canvas.width  = video.videoWidth  || 640
        canvas.height = video.videoHeight || 480
        canvas.getContext('2d')?.drawImage(video, 0, 0)

        canvas.toBlob((blob) => {
            if (!blob) return
            const file = new File([blob], 'photo.jpg', { type: 'image/jpeg' })
            const url  = URL.createObjectURL(blob)
            setCaptured(url)
            setCapturedFile(file)
            setModalStep('confirm')
            stopCamera()
        }, 'image/jpeg', 0.92)
    }, [stopCamera])

    // ── Retake — go back to live view ──────────────────────────────────────────
    const retakePhoto = useCallback(() => {
        if (captured) URL.revokeObjectURL(captured)
        setCaptured(null)
        setCapturedFile(null)
        setModalStep('live')
        setTimeout(() => startCamera(), 80)
    }, [captured, startCamera])

    // ── Confirm captured photo ─────────────────────────────────────────────────
    const confirmPhoto = useCallback(() => {
        if (!captured || !capturedFile) return
        setPreview(captured)
        onCapture?.(capturedFile)
        setModalOpen(false)
    }, [captured, capturedFile, onCapture])

    // ── Close modal (cancel) ───────────────────────────────────────────────────
    const closeModal = useCallback(() => {
        stopCamera()
        if (captured) URL.revokeObjectURL(captured)
        setCaptured(null)
        setCapturedFile(null)
        setCameraError(null)
        setModalOpen(false)
    }, [stopCamera, captured])

    // Stop camera if modal closes externally (e.g. ESC key)
    useEffect(() => {
        if (!modalOpen) stopCamera()
    }, [modalOpen])

    // ── File upload ────────────────────────────────────────────────────────────
    const handleFileChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0]
        if (!file) return
        if (preview) URL.revokeObjectURL(preview)
        const url = URL.createObjectURL(file)
        setPreview(url)
        onCapture?.(file)
        e.target.value = ''
    }, [preview, onCapture])

    // ── Remove photo ───────────────────────────────────────────────────────────
    const removePhoto = useCallback(() => {
        if (preview) URL.revokeObjectURL(preview)
        setPreview(null)
    }, [preview])

    return (
        <>
            {/* ── Card ── */}
            <Card className={cn('flex flex-col justify-between p-4 sm:col-span-1', className)}>
                <CardContent className="flex flex-col items-center gap-3 p-0">
                    <div className="relative w-50 h-50 rounded-full overflow-hidden border-2 border-muted bg-muted shrink-0">
                        <Avatar className="w-full h-full rounded-full">
                            <AvatarImage
                                src={preview ?? undefined}
                                alt="Patient photo"
                                className="object-cover"
                            />
                            <AvatarFallback className="text-lg">{initials}</AvatarFallback>
                        </Avatar>
                    </div>

                    {/* Hidden file input */}
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/*"
                        className="hidden"
                        onChange={handleFileChange}
                    />
                </CardContent>

                <CardFooter className="flex-row justify-center gap-2 p-0 pt-3">
                    {/* Take Photo */}
                    <button
                        type="button"
                        onClick={openCameraModal}
                        title="Take Photo"
                        className="flex flex-col items-center gap-0.5 rounded-md px-3 py-1.5 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                    >
                        <CameraIcon className="h-4 w-4" />
                        <span className="text-[10px]">Camera</span>
                    </button>

                    <div className="w-px h-6 bg-border" />

                    {/* Upload */}
                    <button
                        type="button"
                        onClick={() => fileInputRef.current?.click()}
                        title="Upload Photo"
                        className="flex flex-col items-center gap-0.5 rounded-md px-3 py-1.5 text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
                    >
                        <UploadIcon className="h-4 w-4" />
                        <span className="text-[10px]">Upload</span>
                    </button>

                    {preview && (
                        <>
                            <div className="w-px h-6 bg-border" />
                            {/* Remove */}
                            <button
                                type="button"
                                onClick={removePhoto}
                                title="Remove Photo"
                                className="flex flex-col items-center gap-0.5 rounded-md px-3 py-1.5 text-destructive hover:bg-destructive/10 transition-colors"
                            >
                                <XIcon className="h-4 w-4" />
                                <span className="text-[10px]">Remove</span>
                            </button>
                        </>
                    )}
                </CardFooter>
            </Card>

            {/* ── Camera Modal ── */}
            <Dialog open={modalOpen} onOpenChange={(open) => { if (!open) closeModal() }}>
                <DialogContent className="sm:max-w-md p-0 overflow-hidden gap-0">
                    <DialogHeader className="px-5 pt-5 pb-3">
                        <DialogTitle className="flex items-center gap-2 text-base">
                            <CameraIcon className="h-4 w-4" />
                            {modalStep === 'live' ? 'Take a Photo' : 'Use this photo?'}
                        </DialogTitle>
                        <DialogDescription className="text-xs text-muted-foreground">
                            {modalStep === 'live'
                                ? 'Position the patient\'s face in the frame and press Capture.'
                                : 'Review the photo below. You can retake it or confirm to use it.'}
                        </DialogDescription>
                    </DialogHeader>

                    {/* ── Viewfinder ── */}
                    <div className="relative w-full aspect-[4/3] bg-black overflow-hidden">

                        {/* Live video */}
                        {modalStep === 'live' && (
                            <>
                                <video
                                    ref={videoRef}
                                    autoPlay
                                    playsInline
                                    muted
                                    className="w-full h-full object-cover scale-x-[-1]"
                                />
                                {/* Face guide overlay */}
                                <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                                    <div className="w-48 h-56 rounded-full border-2 border-white/50 border-dashed" />
                                </div>
                                {/* Error overlay */}
                                {cameraError && (
                                    <div className="absolute inset-0 flex items-center justify-center bg-black/70 px-6">
                                        <p className="text-white text-sm text-center leading-relaxed">{cameraError}</p>
                                    </div>
                                )}
                            </>
                        )}

                        {/* Captured preview */}
                        {modalStep === 'confirm' && captured && (
                            <img
                                src={captured}
                                alt="Captured"
                                className="w-full h-full object-cover scale-x-[-1]"
                            />
                        )}

                        {/* Hidden canvas for snapshot */}
                        <canvas ref={canvasRef} className="hidden" />
                    </div>

                    {/* ── Footer buttons ── */}
                    <DialogFooter className="flex flex-row gap-2 px-5 py-4 sm:justify-between">
                        {modalStep === 'live' ? (
                            <>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={closeModal}
                                    className="flex-1"
                                >
                                    <XIcon className="h-4 w-4 mr-1.5" />
                                    Cancel
                                </Button>
                                <Button
                                    type="button"
                                    onClick={capturePhoto}
                                    disabled={!!cameraError}
                                    className="flex-1 bg-green-600 hover:bg-green-700 text-white"
                                >
                                    <CameraIcon className="h-4 w-4 mr-1.5" />
                                    Capture
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={retakePhoto}
                                    className="flex-1"
                                >
                                    <RefreshCwIcon className="h-4 w-4 mr-1.5" />
                                    Retake
                                </Button>
                                <Button
                                    type="button"
                                    onClick={confirmPhoto}
                                    className="flex-1 bg-green-600 hover:bg-green-700 text-white"
                                >
                                    <CheckIcon className="h-4 w-4 mr-1.5" />
                                    Use Photo
                                </Button>
                            </>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    )
}