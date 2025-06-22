"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Star, Search, Filter, Heart, MessageCircle } from "lucide-react"
import Link from "next/link"

const freelancers = [
  {
    id: 1,
    name: "Sarah Kimani",
    title: "Full-Stack Developer",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.9,
    reviews: 127,
    price: "From $25/hr",
    skills: ["React", "Node.js", "Python"],
    description: "I build modern web applications with clean code and great user experience.",
    location: "Nairobi, Kenya",
    verified: true,
  },
  {
    id: 2,
    name: "Ahmed Hassan",
    title: "UI/UX Designer",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.8,
    reviews: 89,
    price: "From $30/hr",
    skills: ["Figma", "Adobe XD", "Prototyping"],
    description: "Creating beautiful and functional designs that convert users into customers.",
    location: "Cairo, Egypt",
    verified: true,
  },
  {
    id: 3,
    name: "Grace Mwangi",
    title: "Content Writer",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.7,
    reviews: 156,
    price: "From $15/hr",
    skills: ["SEO Writing", "Copywriting", "Blog Posts"],
    description: "Engaging content that drives traffic and converts readers into customers.",
    location: "Kampala, Uganda",
    verified: true,
  },
  {
    id: 4,
    name: "David Ochieng",
    title: "Mobile App Developer",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.9,
    reviews: 203,
    price: "From $35/hr",
    skills: ["Flutter", "React Native", "iOS"],
    description: "Building cross-platform mobile apps that users love to use.",
    location: "Mombasa, Kenya",
    verified: true,
  },
  {
    id: 5,
    name: "Fatima Al-Zahra",
    title: "Digital Marketer",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.8,
    reviews: 94,
    price: "From $20/hr",
    skills: ["Google Ads", "Facebook Ads", "SEO"],
    description: "Driving growth through data-driven digital marketing strategies.",
    location: "Dubai, UAE",
    verified: true,
  },
  {
    id: 6,
    name: "John Mutua",
    title: "Video Editor",
    avatar: "/placeholder.svg?height=40&width=40",
    rating: 4.6,
    reviews: 78,
    price: "From $18/hr",
    skills: ["Premiere Pro", "After Effects", "DaVinci"],
    description: "Professional video editing for brands, creators, and businesses.",
    location: "Nairobi, Kenya",
    verified: true,
  },
]

export default function FreelancersPage() {
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
            <Link href="/freelancers" className="text-blue-600 font-medium">
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
          <Button>Post Project</Button>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8">
        {/* Search and Filters */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-6">Find Freelancers</h1>
          <div className="flex flex-col md:flex-row gap-4 mb-6">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
              <Input placeholder="Search for services..." className="pl-10" />
            </div>
            <Select>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder="Category" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="development">Development</SelectItem>
                <SelectItem value="design">Design</SelectItem>
                <SelectItem value="writing">Writing</SelectItem>
                <SelectItem value="marketing">Marketing</SelectItem>
                <SelectItem value="video">Video & Animation</SelectItem>
              </SelectContent>
            </Select>
            <Select>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder="Budget" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="under-25">Under $25/hr</SelectItem>
                <SelectItem value="25-50">$25-50/hr</SelectItem>
                <SelectItem value="50-100">$50-100/hr</SelectItem>
                <SelectItem value="over-100">$100+/hr</SelectItem>
              </SelectContent>
            </Select>
            <Button variant="outline" className="flex items-center gap-2">
              <Filter className="h-4 w-4" />
              More Filters
            </Button>
          </div>
        </div>

        {/* Results */}
        <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          {freelancers.map((freelancer) => (
            <Card key={freelancer.id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-3">
                    <Avatar>
                      <AvatarImage src={freelancer.avatar || "/placeholder.svg"} />
                      <AvatarFallback>
                        {freelancer.name
                          .split(" ")
                          .map((n) => n[0])
                          .join("")}
                      </AvatarFallback>
                    </Avatar>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-semibold">{freelancer.name}</h3>
                        {freelancer.verified && (
                          <Badge variant="secondary" className="text-xs">
                            Verified
                          </Badge>
                        )}
                      </div>
                      <p className="text-sm text-gray-600">{freelancer.title}</p>
                      <p className="text-xs text-gray-500">{freelancer.location}</p>
                    </div>
                  </div>
                  <Button variant="ghost" size="icon">
                    <Heart className="h-4 w-4" />
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <div className="flex items-center gap-1 mb-3">
                  <Star className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                  <span className="font-medium">{freelancer.rating}</span>
                  <span className="text-gray-500 text-sm">({freelancer.reviews} reviews)</span>
                </div>
                <p className="text-sm text-gray-600 mb-4">{freelancer.description}</p>
                <div className="flex flex-wrap gap-1 mb-4">
                  {freelancer.skills.map((skill) => (
                    <Badge key={skill} variant="outline" className="text-xs">
                      {skill}
                    </Badge>
                  ))}
                </div>
                <div className="flex items-center justify-between">
                  <span className="font-semibold text-blue-600">{freelancer.price}</span>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm">
                      <MessageCircle className="h-4 w-4 mr-1" />
                      Message
                    </Button>
                    <Button size="sm">Hire Now</Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Load More */}
        <div className="text-center mt-12">
          <Button variant="outline" size="lg">
            Load More Freelancers
          </Button>
        </div>
      </div>
    </div>
  )
}