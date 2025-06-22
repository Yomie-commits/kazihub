"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Progress } from "@/components/ui/progress"
import {
  Bell,
  Briefcase,
  Calendar,
  DollarSign,
  Eye,
  Heart,
  MapPin,
  MessageCircle,
  Plus,
  TrendingUp,
  CheckCircle,
  AlertCircle,
} from "lucide-react"
import Link from "next/link"
import { useAuth } from "@/contexts/auth-context"
import { ProtectedRoute } from "@/components/auth/protected-route"

const recentApplications = [
  {
    id: 1,
    title: "Senior Software Engineer",
    company: "TechCorp Dubai",
    type: "UAE Job",
    status: "Under Review",
    appliedDate: "2 days ago",
    salary: "AED 20,000/month",
  },
  {
    id: 2,
    title: "Plumber Needed - Kitchen Repair",
    client: "Mary Wanjiku",
    type: "Juakali Job",
    status: "Accepted",
    appliedDate: "1 week ago",
    salary: "KSh 4,000",
  },
  {
    id: 3,
    title: "UI/UX Design Project",
    client: "StartupXYZ",
    type: "Freelance",
    status: "In Progress",
    appliedDate: "2 weeks ago",
    salary: "$1,200",
  },
]

const savedJobs = [
  {
    id: 1,
    title: "Digital Marketing Manager",
    company: "Gulf Marketing Solutions",
    location: "Dubai, UAE",
    salary: "AED 15,000/month",
    type: "UAE Job",
  },
  {
    id: 2,
    title: "House Cleaning Service",
    client: "James Kiprotich",
    location: "Karen, Nairobi",
    salary: "KSh 2,500/week",
    type: "Juakali Job",
  },
]

const messages = [
  {
    id: 1,
    from: "Sarah from TechCorp",
    message: "We'd like to schedule an interview for the Software Engineer position.",
    time: "2 hours ago",
    unread: true,
  },
  {
    id: 2,
    from: "Mary Wanjiku",
    message: "Thank you for the excellent plumbing work! Here's your payment.",
    time: "1 day ago",
    unread: false,
  },
  {
    id: 3,
    from: "KaziHub Support",
    message: "Your profile verification has been completed successfully.",
    time: "3 days ago",
    unread: false,
  },
]

export default function DashboardPage() {
  const { user, logout } = useAuth()

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
              <Link href="/dashboard" className="text-blue-600 font-medium">
                Dashboard
              </Link>
            </nav>
            <div className="flex items-center gap-2">
              <Button variant="ghost" size="icon">
                <Bell className="h-4 w-4" />
              </Button>
              <Button variant="ghost" onClick={logout}>
                Logout
              </Button>
              <Avatar>
                <AvatarImage src={user?.avatar || "/placeholder.svg"} />
                <AvatarFallback>
                  {user?.name
                    ?.split(" ")
                    .map((n) => n[0])
                    .join("") || "U"}
                </AvatarFallback>
              </Avatar>
            </div>
          </div>
        </header>

        <div className="container mx-auto px-4 py-8">
          {/* Welcome Section */}
          <div className="mb-8">
            <h1 className="text-3xl font-bold mb-2">Welcome back, {user?.name?.split(" ")[0] || "User"}!</h1>
            <p className="text-gray-600">Here's what's happening with your KaziHub account</p>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">Active Applications</p>
                    <p className="text-2xl font-bold">12</p>
                  </div>
                  <Briefcase className="h-8 w-8 text-blue-600" />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">Profile Views</p>
                    <p className="text-2xl font-bold">89</p>
                  </div>
                  <Eye className="h-8 w-8 text-green-600" />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">This Month Earnings</p>
                    <p className="text-2xl font-bold">$2,450</p>
                  </div>
                  <DollarSign className="h-8 w-8 text-purple-600" />
                </div>
              </CardContent>
            </Card>
            <Card>
              <CardContent className="p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-medium text-gray-600">Success Rate</p>
                    <p className="text-2xl font-bold">94%</p>
                  </div>
                  <TrendingUp className="h-8 w-8 text-orange-600" />
                </div>
              </CardContent>
            </Card>
          </div>

          <div className="grid lg:grid-cols-3 gap-8">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-8">
              {/* Profile Completion */}
              <Card>
                <CardHeader>
                  <CardTitle>Complete Your Profile</CardTitle>
                  <CardDescription>A complete profile gets 3x more views and job opportunities</CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <span className="text-sm">Profile Completion</span>
                      <span className="text-sm font-medium">75%</span>
                    </div>
                    <Progress value={75} className="h-2" />
                    <div className="space-y-2">
                      <div className="flex items-center gap-2 text-sm">
                        <CheckCircle className="h-4 w-4 text-green-500" />
                        <span>Basic information added</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm">
                        <CheckCircle className="h-4 w-4 text-green-500" />
                        <span>Profile photo uploaded</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm">
                        <AlertCircle className="h-4 w-4 text-orange-500" />
                        <span>Add work experience</span>
                      </div>
                      <div className="flex items-center gap-2 text-sm">
                        <AlertCircle className="h-4 w-4 text-orange-500" />
                        <span>Upload portfolio samples</span>
                      </div>
                    </div>
                    <Button className="w-full">Complete Profile</Button>
                  </div>
                </CardContent>
              </Card>

              {/* Recent Applications */}
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle>Recent Applications</CardTitle>
                    <Button variant="outline" size="sm">
                      View All
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {recentApplications.map((application) => (
                      <div key={application.id} className="flex items-center justify-between p-4 border rounded-lg">
                        <div className="flex-1">
                          <h4 className="font-medium">{application.title}</h4>
                          <p className="text-sm text-gray-600">
                            {application.company || application.client} • {application.type}
                          </p>
                          <p className="text-xs text-gray-500">Applied {application.appliedDate}</p>
                        </div>
                        <div className="text-right">
                          <Badge
                            variant={
                              application.status === "Accepted"
                                ? "default"
                                : application.status === "In Progress"
                                  ? "secondary"
                                  : "outline"
                            }
                            className="mb-2"
                          >
                            {application.status}
                          </Badge>
                          <p className="text-sm font-medium text-green-600">{application.salary}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* Quick Actions */}
              <Card>
                <CardHeader>
                  <CardTitle>Quick Actions</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <Button variant="outline" className="h-20 flex-col gap-2">
                      <Plus className="h-5 w-5" />
                      <span className="text-xs">Post Service</span>
                    </Button>
                    <Button variant="outline" className="h-20 flex-col gap-2">
                      <Briefcase className="h-5 w-5" />
                      <span className="text-xs">Find Jobs</span>
                    </Button>
                    <Button variant="outline" className="h-20 flex-col gap-2">
                      <MessageCircle className="h-5 w-5" />
                      <span className="text-xs">Messages</span>
                    </Button>
                    <Button variant="outline" className="h-20 flex-col gap-2">
                      <Calendar className="h-5 w-5" />
                      <span className="text-xs">Schedule</span>
                    </Button>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Messages */}
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Messages</CardTitle>
                    <Button variant="ghost" size="sm">
                      View All
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  {messages.map((message) => (
                    <div key={message.id} className="flex gap-3 p-3 border rounded-lg">
                      <Avatar className="h-8 w-8">
                        <AvatarFallback>
                          {message.from
                            .split(" ")
                            .map((n) => n[0])
                            .join("")}
                        </AvatarFallback>
                      </Avatar>
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-medium text-sm">{message.from}</span>
                          {message.unread && <div className="w-2 h-2 bg-blue-600 rounded-full"></div>}
                        </div>
                        <p className="text-xs text-gray-600 line-clamp-2">{message.message}</p>
                        <p className="text-xs text-gray-500 mt-1">{message.time}</p>
                      </div>
                    </div>
                  ))}
                </CardContent>
              </Card>

              {/* Saved Jobs */}
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <CardTitle className="text-lg">Saved Jobs</CardTitle>
                    <Button variant="ghost" size="sm">
                      View All
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="space-y-4">
                  {savedJobs.map((job) => (
                    <div key={job.id} className="p-3 border rounded-lg">
                      <div className="flex items-start justify-between mb-2">
                        <h4 className="font-medium text-sm">{job.title}</h4>
                        <Button variant="ghost" size="icon" className="h-6 w-6">
                          <Heart className="h-3 w-3 fill-red-500 text-red-500" />
                        </Button>
                      </div>
                      <p className="text-xs text-gray-600">{job.company || job.client}</p>
                      <div className="flex items-center gap-1 text-xs text-gray-500 mb-2">
                        <MapPin className="h-3 w-3" />
                        {job.location}
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-xs font-medium text-green-600">{job.salary}</span>
                        <Badge variant="outline" className="text-xs">
                          {job.type}
                        </Badge>
                      </div>
                    </div>
                  ))}
                </CardContent>
              </Card>

              {/* Performance Stats */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-lg">This Month</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="flex items-center justify-between">
                    <span className="text-sm">Jobs Applied</span>
                    <span className="font-medium">8</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm">Interviews</span>
                    <span className="font-medium">3</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm">Jobs Completed</span>
                    <span className="font-medium">5</span>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm">Earnings</span>
                    <span className="font-medium text-green-600">$2,450</span>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>
    </ProtectedRoute>
  )
}