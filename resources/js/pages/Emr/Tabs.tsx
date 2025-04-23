import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs"

export default function TabsAction() {
  return (
    <Tabs defaultValue="account" className="w-[400px]">
      <TabsList className="grid w-full grid-cols-2">
        <TabsTrigger value="account">Account</TabsTrigger>
        <TabsTrigger value="password">Password</TabsTrigger>
      </TabsList>
      <TabsContent value="account">
        <div className="space-y-4 p-4">
          <h3 className="text-lg font-semibold">Account</h3>
          <p className="text-sm text-gray-500">
            Make changes to your account here. Click save when you're done.
          </p>
          
          <div className="space-y-4">
            <div className="space-y-1">
              <Label htmlFor="name">Name</Label>
              <Input id="name" defaultValue="Pedro Duarte" />
            </div>
            <div className="space-y-1">
              <Label htmlFor="username">Username</Label>
              <Input id="username" defaultValue="@peduarte" />
            </div>
          </div>

          <Button className="mt-4">Save changes</Button>
        </div>
      </TabsContent>
      <TabsContent value="password">
        <div className="space-y-4 p-4">
          <h3 className="text-lg font-semibold">Password</h3>
          <p className="text-sm text-gray-500">
            Change your password here. After saving, you'll be logged out.
          </p>

          <div className="space-y-4">
            <div className="space-y-1">
              <Label htmlFor="current">Current password</Label>
              <Input id="current" type="password" />
            </div>
            <div className="space-y-1">
              <Label htmlFor="new">New password</Label>
              <Input id="new" type="password" />
            </div>
          </div>

          <Button className="mt-4">Save password</Button>
        </div>
      </TabsContent>
    </Tabs>
  )
}
