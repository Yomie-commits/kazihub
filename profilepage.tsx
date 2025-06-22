"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Progress } from "@/components/ui/progress"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { useAuth } from "@/contexts/auth-context"
import { ProtectedRoute } from "@/components/auth/protected-route"
import { MapPin, Star, Camera, X, Plus, CheckCircle, AlertCircle } from "lucide-react"
import Link from "next/link"

export default function ProfilePage() {
  const { user, updateProfile } = useAuth()
  const [isEditing, setIsEditing] = useState(false)
  const [formData, setFormData] = useState({
    name: user?.name || "",
    email: user?.email || "",
    location: user?.location || "",
    phone: user?.phone || "",
    bio: user?.bio || "",
    skills: user?.skills || [],
    experience: user?.experience || "",
    hourlyRate: user?.hourlyRate || "",
    portfolio: user?.portfolio || [],
    companyName: user?.companyName || "",
    companySize: user?.companySize || "",
    industry: user?.industry || "",
  })
  const [newSkill, setNewSkill] = useState("")

  const handleSave = async () => {
    await updateProfile(formData)
    setIsEditing(false)
  }

  const addSkill = () => {
    if (newSkill.trim() && !formData.skills.includes(newSkill.trim())) {
      setFormData({
        ...formData,
        skills: [...formData.skills, newSkill.trim()],
      })
      setNewSkill("")
    }
  }

  const removeSkill = (skillToRemove: string) => {
    setFormData({
      ...formData,
      skills: formData.skills.filter((skill) => skill !== skillToRemove),
    })
  }

  const calculateProfileCompletion = () => {
    const requiredFields = ["name", "email", "location", "phone", "bio"]
    const userTypeFields = {
      freelancer: ["skills", "experience", "hourlyRate"],
      client: ["companyName", "industry"],
      job_seeker: ["skills", "experience"],
    }

    const allRequiredFields = [...requiredFields, ...userTypeFields[user?.userType || "freelancer"]]
    const completedFields = allRequiredFields.filter((field) => {
      const value = user?.[field as keyof typeof user]
      return Array.isArray(value) ? value.length > 0 : Boolean(value)
    })

    return Math.round((completedFields.length / allRequiredFields.length) * 100)
  }

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-gray-50">
        {/* Header */}
        <header className="border-b bg-white sticky top-0 z-50">
          <div className="container mx-auto px-4 py-4 flex items-center justify-between">
            <Link href="/" className="flex items-center gap-2">
              <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <span className="text-white font-bold text-sm">K</span>
              </div>
              <span className="text-xl font-bold text-gray-900">KaziHub</span>
            </Link>
            <nav className="hidden md:flex items-center gap-6">
              <Link href="/freelancers" className="text-gray-600 hover:text-blue-600">
                Freelancers
              </Link>
              <Link href="/juakali" className="text-gray-600 hover:text-blue-600">
                Juakali Jobs
              </Link>
              <Link href="/uae-jobs" className="text-gray-600 hover:text-blue-600">
                UAE Jobs
              </Link>
              <Link href="/dashboard" className="text-gray-600 hover:text-blue-600">
                Dashboard
              </Link>
            </nav>
            <div className="flex items-center gap-2">
              <Button variant="outline" onClick={() => setIsEditing(!isEditing)}>
                {isEditing ? "Cancel" : "Edit Profile"}
              </Button>
              {isEditing && <Button onClick={handleSave}>Save Changes</Button>}
            </div>
          </div>
        </header>

        <div className="container mx-auto px-4 py-8 max-w-4xl">
          {/* Profile Header */}
          <Card className="mb-8">
            <CardContent className="p-6">
              <div className="flex items-start gap-6">
                <div className="relative">
                  <Avatar className="h-24 w-24">
                    <AvatarImage src={user?.avatar || "/placeholder.svg"} />
                    <AvatarFallback className="text-2xl">
                      {user?.name
                        ?.split(" ")
                        .map((n) => n[0])
                        .join("") || "U"}
                    </AvatarFallback>
                  </Avatar>
                  {isEditing && (
                    <Button size="icon" variant="outline" className="absolute -bottom-2 -right-2 h-8 w-8 rounded-full">
                      <Camera className="h-4 w-4" />
                    </Button>
                  )}
                </div>
                <div className="flex-1">
                  <div className="flex items-center gap-3 mb-2">
                    <h1 className="text-2xl font-bold">{user?.name}</h1>
                    {user?.verified && (
                      <Badge variant="secondary" className="flex items-center gap-1">
                        <CheckCircle className="h-3 w-3" />
                        Verified
                      </Badge>
                    )}
                  </div>
                  <p className="text-gray-600 mb-2 capitalize">{user?.userType?.replace("_", " ")}</p>
                  {user?.location && (
                    <div className="flex items-center gap-1 text-gray-600 mb-4">
                      <MapPin className="h-4 w-4" />
                      <span>{user.location}</span>
                    </div>
                  )}
                  <div className="flex items-center gap-4">
                    <div className="flex items-center gap-1">
                      <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                      <span className="font-medium">4.8</span>
                      <span className="text-gray-600 text-sm">(127 reviews)</span>
                    </div>
                    {user?.userType === "freelancer" && user?.hourlyRate && (
                      <div className="text-green-600 font-medium">{user.hourlyRate}</div>
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          {/* Profile Completion */}
          {calculateProfileCompletion() < 100 && (
            <Card className="mb-8">
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <AlertCircle className="h-5 w-5 text-orange-500" />
                  Complete Your Profile
                </CardTitle>
                <CardDescription>A complete profile gets 3x more views and opportunities</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm">Profile Completion</span>
                    <span className="text-sm font-medium">{calculateProfileCompletion()}%</span>
                  </div>
                  <Progress value={calculateProfileCompletion()} className="h-2" />
                </div>
              </CardContent>
            </Card>
          )}

          {/* Profile Tabs */}
          <Tabs defaultValue="overview" className="space-y-6">
            <TabsList className="grid w-full grid-cols-4">
              <TabsTrigger value="overview">Overview</TabsTrigger>
              <TabsTrigger value="details">Details</TabsTrigger>
              <TabsTrigger value="portfolio">Portfolio</TabsTrigger>
              <TabsTrigger value="settings">Settings</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" className="space-y-6">
              {/* Bio */}
              <Card>
                <CardHeader>
                  <CardTitle>About</CardTitle>
                </CardHeader>
                <CardContent>
                  {isEditing ? (
                    <Textarea
                      value={formData.bio}
                      onChange={(e) => setFormData({ ...formData, bio: e.target.value })}
                      placeholder="Tell us about yourself..."
                      rows={4}
                    />
                  ) : (
                    <p className="text-gray-700">{user?.bio || 'No bio added yet. Click "Edit Profile" to add one.'}</p>
                  )}
                </CardContent>
              </Card>

              {/* Skills */}
              {(user?.userType === "freelancer" || user?.userType === "job_seeker") && (
                <Card>
                  <CardHeader>
                    <CardTitle>Skills</CardTitle>
                  </CardHeader>
                  <CardContent>
                    {isEditing ? (
                      <div className="space-y-3">
                        <div className="flex gap-2">
                          <Input
                            value={newSkill}
                            onChange={(e) => setNewSkill(e.target.value)}
                            placeholder="Add a skill"
                            onKeyPress={(e) => e.key === "Enter" && addSkill()}
                          />
                          <Button type="button" onClick={addSkill}>
                            Add
                          </Button>
                        </div>
                        {formData.skills.length > 0 && (
                          <div className="flex flex-wrap gap-2">
                            {formData.skills.map((skill) => (
                              <Badge key={skill} variant="secondary" className="flex items-center gap-1">
                                {skill}
                                <X className="h-3 w-3 cursor-pointer" onClick={() => removeSkill(skill)} />
                              </Badge>
                            ))}
                          </div>
                        )}
                      </div>
                    ) : (
                      <div className="flex flex-wrap gap-2">
                        {user?.skills?.length ? (
                          user.skills.map((skill) => (
                            <Badge key={skill} variant="outline">
                              {skill}
                            </Badge>
                          ))
                        ) : (
                          <p className="text-gray-500">No skills added yet.</p>
                        )}
                      </div>
                    )}
                  </CardContent>
                </Card>
              )}

              {/* Company Info for Clients */}
              {user?.userType === "client" && (
                <Card>
                  <CardHeader>
                    <CardTitle>Company Information</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div>
                        <Label>Company Name</Label>
                        <p className="text-gray-700">{user?.companyName || "Not specified"}</p>
                      </div>
                      <div>
                        <Label>Industry</Label>
                        <p className="text-gray-700 capitalize">{user?.industry || "Not specified"}</p>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              )}
            </TabsContent>

            <TabsContent value="details" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Personal Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-2">
                      <Label htmlFor="name">Full Name</Label>
                      {isEditing ? (
                        <Input
                          id="name"
                          value={formData.name}
                          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        />
                      ) : (
                        <p className="text-gray-700">{user?.name}</p>
                      )}
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="email">Email</Label>
                      <p className="text-gray-700">{user?.email}</p>
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="location">Location</Label>
                      {isEditing ? (
                        <Input
                          id="location"
                          value={formData.location}
                          onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                        />
                      ) : (
                        <p className="text-gray-700">{user?.location || "Not specified"}</p>
                      )}
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="phone">Phone</Label>
                      {isEditing ? (
                        <Input
                          id="phone"
                          value={formData.phone}
                          onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                        />
                      ) : (
                        <p className="text-gray-700">{user?.phone || "Not specified"}</p>
                      )}
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Professional Details */}
              {(user?.userType === "freelancer" || user?.userType === "job_seeker") && (
                <Card>
                  <CardHeader>
                    <CardTitle>Professional Information</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4">
                    <div className="grid grid-cols-2 gap-4">
                      <div className="space-y-2">
                        <Label>Experience Level</Label>
                        {isEditing ? (
                          <Select
                            value={formData.experience}
                            onValueChange={(value) => setFormData({ ...formData, experience: value })}
                          >
                            <SelectTrigger>
                              <SelectValue placeholder="Select experience level" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="entry">Entry Level (0-2 years)</SelectItem>
                              <SelectItem value="intermediate">Intermediate (2-5 years)</SelectItem>
                              <SelectItem value="senior">Senior (5-10 years)</SelectItem>
                              <SelectItem value="expert">Expert (10+ years)</SelectItem>
                            </SelectContent>
                          </Select>
                        ) : (
                          <p className="text-gray-700 capitalize">{user?.experience || "Not specified"}</p>
                        )}
                      </div>
                      {user?.userType === "freelancer" && (
                        <div className="space-y-2">
                          <Label>Hourly Rate</Label>
                          {isEditing ? (
                            <Input
                              value={formData.hourlyRate}
                              onChange={(e) => setFormData({ ...formData, hourlyRate: e.target.value })}
                              placeholder="e.g. $25/hour"
                            />
                          ) : (
                            <p className="text-gray-700">{user?.hourlyRate || "Not specified"}</p>
                          )}
                        </div>
                      )}
                    </div>
                  </CardContent>
                </Card>
              )}
            </TabsContent>

            <TabsContent value="portfolio" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Portfolio</CardTitle>
                  <CardDescription>Showcase your best work</CardDescription>
                </CardHeader>
                <CardContent>
                  {user?.portfolio?.length ? (
                    <div className="space-y-4">
                      {user.portfolio.map((item, index) => (
                        <div key={index} className="p-4 border rounded-lg">
                          <p className="text-sm text-gray-600">{item}</p>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <p className="text-gray-500 mb-4">No portfolio items yet</p>
                      <Button variant="outline">
                        <Plus className="h-4 w-4 mr-2" />
                        Add Portfolio Item
                      </Button>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="settings" className="space-y-6">
              <Card>
                <CardHeader>
                  <CardTitle>Account Settings</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <Button variant="outline" className="w-full justify-start">
                    Change Password
                  </Button>
                  <Button variant="outline" className="w-full justify-start">
                    Email Preferences
                  </Button>
                  <Button variant="outline" className="w-full justify-start">
                    Privacy Settings
                  </Button>
                  <Button variant="destructive" className="w-full justify-start">
                    Delete Account
                  </Button>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </div>
      </div>
    </ProtectedRoute>
  )
}