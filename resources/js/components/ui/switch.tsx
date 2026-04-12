import * as React from "react"
import * as SwitchPrimitive from "@radix-ui/react-switch"

import { cn } from "@/lib/utils"

function Switch({
  className,
  ...props
}: React.ComponentProps<typeof SwitchPrimitive.Root>) {
  return (
    <SwitchPrimitive.Root
      data-slot="switch"
      className={cn(
        // Keep original layout & size
        "peer inline-flex h-[1.15rem] w-8 shrink-0 items-center rounded-full border shadow-xs outline-none",
        // Transition
        "transition-all duration-300",
        // Checked — vivid green + glow
        "data-[state=checked]:bg-emerald-500",
        "data-[state=checked]:border-emerald-400",
        "data-[state=checked]:shadow-[0_0_8px_2px_rgba(52,211,153,0.55)]",
        // Unchecked — vivid red + glow
        "data-[state=unchecked]:bg-rose-500",
        "data-[state=unchecked]:border-rose-400",
        "data-[state=unchecked]:shadow-[0_0_6px_2px_rgba(244,63,94,0.55)]",
        "dark:data-[state=unchecked]:bg-rose-600",
        "dark:data-[state=unchecked]:border-rose-500",
        // Focus
        "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[1px]",
        // Disabled
        "disabled:cursor-not-allowed disabled:opacity-50",
        className
      )}
      {...props}
    >
      <SwitchPrimitive.Thumb
        data-slot="switch-thumb"
        className={cn(
          "pointer-events-none block size-4 rounded-full ring-0",
          "transition-all duration-300",
          "data-[state=checked]:translate-x-[calc(100%-2px)] data-[state=unchecked]:translate-x-0",
          // Thumb
          "data-[state=checked]:bg-white data-[state=checked]:shadow-[0_0_4px_rgba(52,211,153,0.6)]",
          "data-[state=unchecked]:bg-white data-[state=unchecked]:shadow-[0_0_4px_rgba(244,63,94,0.6)]",
          "dark:data-[state=checked]:bg-primary-foreground",
        )}
      />
    </SwitchPrimitive.Root>
  )
}

export { Switch }