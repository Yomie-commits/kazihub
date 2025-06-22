"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { MapPin, Clock, Star, Search, Phone, MessageCircle, CheckCircle } from "lucide-react"
import Link from "next/link"

const juakaliJobs = [
  {
    id: 1,
    title: "Plumber Needed - Kitchen Repair",
    description: "Need an experienced plumber to fix kitchen sink and install new faucet.",
    location: "Westlands, Nairobi",
    distance: "2.3 km away",
    budget: "KSh 3,000 - 5,000",
    timePosted: "2 hours ago",
    urgency: "Urgent",
    skills: ["Plumbing", "Pipe Fitting"],
    client: {
      name: "Mary Wanjiku",
      avatar: "/placeholder.svg?height=32&width=32",
      rating: 4.8,
      verified: true,
    },
  },
  {
    id: 2,
    title: "House Cleaning Service",
    description: "Weekly house cleaning for 3-bedroom apartment. Must be reliable and thorough.",
    location: "Karen, Nairobi",
    distance: "5.1 km away",
    budget: "KSh 2,500/week",
    timePosted: "4 hours ago",
    urgency: "Regular",
    skills: ["House Cleaning", "Laundry"],
    client: {
      name: "James Kiprotich",
      avatar: "/placeholder.svg?height=32&width=32",
      rating: 4.9,
      verified: true,
    },
  },
  {
    id: 3,
    title: "Carpenter - Custom Furniture",
    description: "Looking for skilled carpenter to build custom dining table and chairs.",
    location: "Kilimani, Nairobi",
    distance: "1.8 km away",
    budget: "KSh 15,000 - 25,000",
    timePosted: "6 hours ago",
    urgency: "This Week",
    skills: ["Carpentry", "Furniture Making"],
    client: {
      name: "Susan Muthoni",
      avatar: "/placeholder.svg?height=32&width=32",
      rating: 4.7,
      verified: true,
    },
  },
  {
    id: 4,
    title: "Electrician - Wiring Installation",
    description: "Need certified electrician for new house wiring and electrical panel installation.",
    location: "Kasarani, Nairobi",
    distance: "8.2 km away",
    budget: "KSh 20,000 - 30,000",
    timePosted: "1 day ago",
    urgency: "This Week",
    skills: ["Electrical Work", "Wiring"],
    client: {
      name: "Peter Omondi",
      avatar: "/placeholder.svg?height=32&width=32",
      rating: 4.6,
      verified: true,
    },
  },
  {
    id: 5,
    title: "Garden Maintenance",
    description: "Monthly garden maintenance including lawn mowing, pruning, and weeding.",
    location: "Runda, Nairobi",
    distance: "12.5 km away",
    budget: "KSh 4,000/month",
    timePosted: "1 day ago",
    urgency: "Flexible",
    skills: ["Gardening", "Landscaping"],
    client: {
      name: "Grace Njeri",
      avatar: "/placeholder.svg?height=32&width=32",
      rating: 4.8,
      verified: true,
    },
  },
  {
    id: 6,
    title: "Motorcycle Mechanic",
    description: "Motorcycle needs engine repair and general maintenance. Must be experienced.",
    location: "Eastleigh, Nairobi",
    distance: "6.7 km away",
    budget: "KSh 5,000 - 8,000",
    timePosted: "2 days ago",
    urgency: "This Week",
    skills: ["Motorcycle Repair", "Engine Work"],
    client: {
      name: "Ali Mohamed",
      avatar: "/placeholder.svg?height=32&width=32",
      rating: 4.5,
      verified: true,
    },
  },
]

const availableWorkers = [
  {
    id: 1,
    name: "John Kamau",
    skill: "Plumber",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.9,
    distance: "1.2 km away",
    status: "Available Now",
    hourlyRate: "KSh 800/hr",
    badges: ["Verified", "Top Rated"],
  },
  {
    id: 2,
    name: "Rose Akinyi",
    skill: "House Cleaner",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.8,
    distance: "2.1 km away",
    status: "Available Today",
    hourlyRate: "KSh 500/hr",
    badges: ["Verified", "Reliable"],
  },
  {
    id: 3,
    name: "Samuel Mwangi",
    skill: "Carpenter",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.7,
    distance: "3.5 km away",
    status: "Available Tomorrow",
    hourlyRate: "KSh 1,200/hr",
    badges: ["Verified", "Expert"],
  },
]

export default function JuakaliPage() {
  return (
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
            <Link href="/juakali" className="text-blue-600 font-medium">
              Juakali Jobs
            </Link>
            <Link href="/uae-jobs" className="text-gray-600 hover:text-blue-600">
              UAE Jobs
            </Link>
            <Link href="/dashboard" className="text-gray-600 hover:text-blue-600">
              Dashboard
            </Link>
          </nav>
          <Button>Post Job</Button>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8">
        <div className="grid lg:grid-cols-3 gap-8">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Search and Filters */}
            <div className="mb-8">
              <h1 className="text-3xl font-bold mb-6">Local Juakali Jobs</h1>
              <div className="flex flex-col md:flex-row gap-4 mb-6">
                <div className="flex-1 relative">
                  <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                  <Input placeholder="Search jobs near you..." className="pl-10" />
                </div>
                <Select>
                  <SelectTrigger className="w-full md:w-48">
                    <SelectValue placeholder="Category" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="plumbing">Plumbing</SelectItem>
                    <SelectItem value="cleaning">Cleaning</SelectItem>
                    <SelectItem value="carpentry">Carpentry</SelectItem>
                    <SelectItem value="electrical">Electrical</SelectItem>
                    <SelectItem value="gardening">Gardening</SelectItem>
                  </SelectContent>
                </Select>
                <Select>
                  <SelectTrigger className="w-full md:w-48">
                    <SelectValue placeholder="Distance" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1km">Within 1km</SelectItem>
                    <SelectItem value="5km">Within 5km</SelectItem>
                    <SelectItem value="10km">Within 10km</SelectItem>
                    <SelectItem value="20km">Within 20km</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            {/* Job Listings */}
            <div className="space-y-6">
              {juakaliJobs.map((job) => (
                <Card key={job.id} className="hover:shadow-lg transition-shadow">
                  <CardHeader>
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-2">
                          <CardTitle className="text-lg">{job.title}</CardTitle>
                          {job.urgency === "Urgent" && (
                            <Badge variant="destructive" className="text-xs">
                              Urgent
                            </Badge>
                          )}
                        </div>
                        <CardDescription className="mb-3">{job.description}</CardDescription>
                        <div className="flex flex-wrap gap-4 text-sm text-gray-600 mb-3">
                          <div className="flex items-center gap-1">
                            <MapPin className="h-4 w-4" />
                            {job.location} • {job.distance}
                          </div>
                          <div className="flex items-center gap-1">
                            <Clock className="h-4 w-4" />
                            {job.timePosted}
                          </div>
                        </div>
                        <div className="flex flex-wrap gap-1 mb-3">
                          {job.skills.map((skill) => (
                            <Badge key={skill} variant="outline" className="text-xs">
                              {skill}
                            </Badge>
                          ))}
                        </div>
                      </div>
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                          <Avatar className="h-8 w-8">
                            <AvatarImage src={job.client.avatar || "/placeholder.svg"} />
                            <AvatarFallback>
                              {job.client.name
                                .split(" ")
                                .map((n) => n[0])
                                .join("")}
                            </AvatarFallback>
                          </Avatar>
                          <div>
                            <div className="flex items-center gap-1">
                              <span className="text-sm font-medium">{job.client.name}</span>
                              {job.client.verified && <CheckCircle className="h-3 w-3 text-green-500" />}
                            </div>
                            <div className="flex items-center gap-1">
                              <Star className="h-3 w-3 fill-yellow-400 text-yellow-400" />
                              <span className="text-xs text-gray-600">{job.client.rating}</span>
                            </div>
                          </div>
                        </div>
                        <div className="text-right">
                          <div className="font-semibold text-green-600">{job.budget}</div>
                          <div className="text-xs text-gray-500">{job.urgency}</div>
                        </div>
                      </div>
                      <div className="flex gap-2">
                        <Button variant="outline" size="sm">
                          <MessageCircle className="h-4 w-4 mr-1" />
                          Message
                        </Button>
                        <Button size="sm">Apply Now</Button>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Available Workers Nearby */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Workers Near You</CardTitle>
                <CardDescription>Available professionals in your area</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                {availableWorkers.map((worker) => (
                  <div key={worker.id} className="flex items-center gap-3 p-3 border rounded-lg">
                    <Avatar>
                      <AvatarImage src={worker.avatar || "/placeholder.svg"} />
                      <AvatarFallback>
                        {worker.name
                          .split(" ")
                          .map((n) => n[0])
                          .join("")}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <span className="font-medium text-sm">{worker.name}</span>
                        <div className="flex gap-1">
                          {worker.badges.map((badge) => (
                            <Badge key={badge} variant="secondary" className="text-xs">
                              {badge}
                            </Badge>
                          ))}
                        </div>
                      </div>
                      <div className="text-xs text-gray-600">{worker.skill}</div>
                      <div className="flex items-center gap-2 text-xs text-gray-500">
                        <MapPin className="h-3 w-3" />
                        {worker.distance}
                      </div>
                      <div className="flex items-center justify-between mt-1">
                        <span className="text-xs font-medium text-green-600">{worker.hourlyRate}</span>
                        <span className="text-xs text-green-600">{worker.status}</span>
                      </div>
                    </div>
                    <Button size="sm" variant="outline">
                      <Phone className="h-3 w-3" />
                    </Button>
                  </div>
                ))}
                <Button variant="outline" className="w-full">
                  View All Workers
                </Button>
              </CardContent>
            </Card>

            {/* Quick Actions */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Quick Actions</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <Button className="w-full justify-start">
                  <MapPin className="h-4 w-4 mr-2" />
                  Post Emergency Job
                </Button>
                <Button variant="outline" className="w-full justify-start">
                  <Clock className="h-4 w-4 mr-2" />
                  Schedule for Later
                </Button>
                <Button variant="outline" className="w-full justify-start">
                  <Star className="h-4 w-4 mr-2" />
                  Find Top Rated Workers
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  )
}