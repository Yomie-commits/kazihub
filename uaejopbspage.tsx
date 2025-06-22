"use client"

import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Input } from "@/components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Building, MapPin, DollarSign, Calendar, Search, Bookmark, ExternalLink, Shield, Plane } from "lucide-react"
import Link from "next/link"

const uaeJobs = [
  {
    id: 1,
    title: "Senior Software Engineer",
    company: "TechCorp Dubai",
    logo: "/placeholder.svg?height=48&width=48",
    location: "Dubai, UAE",
    salary: "AED 15,000 - 25,000/month",
    type: "Full-time",
    experience: "5+ years",
    description:
      "Join our growing tech team in Dubai. We're looking for experienced developers to build next-generation applications.",
    requirements: ["React", "Node.js", "AWS", "5+ years experience"],
    benefits: ["Visa Sponsorship", "Health Insurance", "Annual Flights", "Housing Allowance"],
    posted: "2 days ago",
    verified: true,
    visaSponsorship: true,
  },
  {
    id: 2,
    title: "Registered Nurse",
    company: "Dubai Health Authority",
    logo: "/placeholder.svg?height=48&width=48",
    location: "Dubai, UAE",
    salary: "AED 8,000 - 12,000/month",
    type: "Full-time",
    experience: "3+ years",
    description: "Opportunity to work in one of Dubai's leading healthcare facilities with international standards.",
    requirements: ["Nursing Degree", "3+ years experience", "English fluency", "DHA License eligible"],
    benefits: ["Visa Sponsorship", "Health Insurance", "Professional Development", "Accommodation"],
    posted: "1 day ago",
    verified: true,
    visaSponsorship: true,
  },
  {
    id: 3,
    title: "Construction Project Manager",
    company: "Emirates Construction",
    logo: "/placeholder.svg?height=48&width=48",
    location: "Abu Dhabi, UAE",
    salary: "AED 18,000 - 28,000/month",
    type: "Full-time",
    experience: "7+ years",
    description: "Lead major construction projects in Abu Dhabi. Experience with high-rise buildings preferred.",
    requirements: ["Engineering Degree", "PMP Certification", "7+ years experience", "Arabic advantage"],
    benefits: ["Visa Sponsorship", "Company Car", "Annual Bonus", "Family Visa"],
    posted: "3 days ago",
    verified: true,
    visaSponsorship: true,
  },
  {
    id: 4,
    title: "Digital Marketing Manager",
    company: "Gulf Marketing Solutions",
    logo: "/placeholder.svg?height=48&width=48",
    location: "Dubai, UAE",
    salary: "AED 12,000 - 18,000/month",
    type: "Full-time",
    experience: "4+ years",
    description: "Drive digital marketing strategies for clients across the GCC region.",
    requirements: ["Marketing Degree", "Google Ads Certified", "4+ years experience", "Arabic/English"],
    benefits: ["Visa Sponsorship", "Performance Bonus", "Training Budget", "Flexible Hours"],
    posted: "1 week ago",
    verified: true,
    visaSponsorship: true,
  },
  {
    id: 5,
    title: "Executive Chef",
    company: "Luxury Hotel Group",
    logo: "/placeholder.svg?height=48&width=48",
    location: "Dubai, UAE",
    salary: "AED 20,000 - 30,000/month",
    type: "Full-time",
    experience: "10+ years",
    description: "Lead culinary operations at our 5-star hotel. International cuisine experience required.",
    requirements: ["Culinary Arts Degree", "10+ years experience", "5-star hotel experience", "Team leadership"],
    benefits: ["Visa Sponsorship", "Accommodation", "Meals", "Annual Leave Ticket"],
    posted: "5 days ago",
    verified: true,
    visaSponsorship: true,
  },
  {
    id: 6,
    title: "Financial Analyst",
    company: "Dubai Investment Bank",
    logo: "/placeholder.svg?height=48&width=48",
    location: "Dubai, UAE",
    salary: "AED 10,000 - 16,000/month",
    type: "Full-time",
    experience: "3+ years",
    description: "Analyze financial data and provide insights for investment decisions in the MENA region.",
    requirements: ["Finance Degree", "CFA preferred", "3+ years experience", "Excel/PowerBI"],
    benefits: ["Visa Sponsorship", "Health Insurance", "Performance Bonus", "Professional Development"],
    posted: "4 days ago",
    verified: true,
    visaSponsorship: true,
  },
]

export default function UAEJobsPage() {
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
            <Link href="/juakali" className="text-gray-600 hover:text-blue-600">
              Juakali Jobs
            </Link>
            <Link href="/uae-jobs" className="text-blue-600 font-medium">
              UAE Jobs
            </Link>
            <Link href="/dashboard" className="text-gray-600 hover:text-blue-600">
              Dashboard
            </Link>
          </nav>
          <Button>Upload CV</Button>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8">
        {/* Hero Section */}
        <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-8 mb-8">
          <div className="max-w-2xl">
            <h1 className="text-3xl font-bold mb-4">UAE Career Opportunities</h1>
            <p className="text-lg mb-6">
              Discover verified job opportunities in the UAE with visa sponsorship, competitive salaries, and
              comprehensive benefits packages.
            </p>
            <div className="flex flex-wrap gap-4">
              <div className="flex items-center gap-2">
                <Shield className="h-5 w-5" />
                <span>Verified Employers</span>
              </div>
              <div className="flex items-center gap-2">
                <Plane className="h-5 w-5" />
                <span>Visa Assistance</span>
              </div>
              <div className="flex items-center gap-2">
                <DollarSign className="h-5 w-5" />
                <span>Competitive Salaries</span>
              </div>
            </div>
          </div>
        </div>

        {/* Search and Filters */}
        <div className="mb-8">
          <div className="flex flex-col md:flex-row gap-4 mb-6">
            <div className="flex-1 relative">
              <Search className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
              <Input placeholder="Search UAE jobs..." className="pl-10" />
            </div>
            <Select>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder="Industry" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="technology">Technology</SelectItem>
                <SelectItem value="healthcare">Healthcare</SelectItem>
                <SelectItem value="construction">Construction</SelectItem>
                <SelectItem value="finance">Finance</SelectItem>
                <SelectItem value="hospitality">Hospitality</SelectItem>
                <SelectItem value="education">Education</SelectItem>
              </SelectContent>
            </Select>
            <Select>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder="Experience" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="entry">Entry Level</SelectItem>
                <SelectItem value="mid">Mid Level (3-5 years)</SelectItem>
                <SelectItem value="senior">Senior (5+ years)</SelectItem>
                <SelectItem value="executive">Executive (10+ years)</SelectItem>
              </SelectContent>
            </Select>
            <Select>
              <SelectTrigger className="w-full md:w-48">
                <SelectValue placeholder="Salary Range" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="5-10k">AED 5,000 - 10,000</SelectItem>
                <SelectItem value="10-15k">AED 10,000 - 15,000</SelectItem>
                <SelectItem value="15-25k">AED 15,000 - 25,000</SelectItem>
                <SelectItem value="25k+">AED 25,000+</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>

        {/* Job Listings */}
        <div className="space-y-6">
          {uaeJobs.map((job) => (
            <Card key={job.id} className="hover:shadow-lg transition-shadow">
              <CardHeader>
                <div className="flex items-start justify-between">
                  <div className="flex items-start gap-4 flex-1">
                    <Avatar className="h-12 w-12">
                      <AvatarImage src={job.logo || "/placeholder.svg"} />
                      <AvatarFallback>
                        {job.company
                          .split(" ")
                          .map((n) => n[0])
                          .join("")}
                      </AvatarFallback>
                    </Avatar>
                    <div className="flex-1">
                      <div className="flex items-center gap-2 mb-2">
                        <CardTitle className="text-xl">{job.title}</CardTitle>
                        {job.verified && (
                          <Badge variant="secondary" className="text-xs">
                            <Shield className="h-3 w-3 mr-1" />
                            Verified
                          </Badge>
                        )}
                        {job.visaSponsorship && (
                          <Badge className="text-xs bg-green-100 text-green-800">
                            <Plane className="h-3 w-3 mr-1" />
                            Visa Sponsored
                          </Badge>
                        )}
                      </div>
                      <div className="flex items-center gap-4 text-sm text-gray-600 mb-3">
                        <div className="flex items-center gap-1">
                          <Building className="h-4 w-4" />
                          {job.company}
                        </div>
                        <div className="flex items-center gap-1">
                          <MapPin className="h-4 w-4" />
                          {job.location}
                        </div>
                        <div className="flex items-center gap-1">
                          <Calendar className="h-4 w-4" />
                          {job.posted}
                        </div>
                      </div>
                      <CardDescription className="mb-4">{job.description}</CardDescription>

                      {/* Requirements */}
                      <div className="mb-4">
                        <h4 className="font-medium text-sm mb-2">Requirements:</h4>
                        <div className="flex flex-wrap gap-1">
                          {job.requirements.map((req) => (
                            <Badge key={req} variant="outline" className="text-xs">
                              {req}
                            </Badge>
                          ))}
                        </div>
                      </div>

                      {/* Benefits */}
                      <div className="mb-4">
                        <h4 className="font-medium text-sm mb-2">Benefits:</h4>
                        <div className="flex flex-wrap gap-1">
                          {job.benefits.map((benefit) => (
                            <Badge key={benefit} variant="secondary" className="text-xs">
                              {benefit}
                            </Badge>
                          ))}
                        </div>
                      </div>
                    </div>
                  </div>
                  <Button variant="ghost" size="icon">
                    <Bookmark className="h-4 w-4" />
                  </Button>
                </div>
              </CardHeader>
              <CardContent>
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-6">
                    <div>
                      <div className="font-semibold text-green-600 text-lg">{job.salary}</div>
                      <div className="text-sm text-gray-500">
                        {job.type} • {job.experience}
                      </div>
                    </div>
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline">
                      <ExternalLink className="h-4 w-4 mr-2" />
                      View Details
                    </Button>
                    <Button>Apply Now</Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {/* Load More */}
        <div className="text-center mt-12">
          <Button variant="outline" size="lg">
            Load More Jobs
          </Button>
        </div>

        {/* UAE Job Guide */}
        <Card className="mt-12 bg-blue-50 border-blue-200">
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Plane className="h-5 w-5 text-blue-600" />
              UAE Job Application Guide
            </CardTitle>
            <CardDescription>Everything you need to know about working in the UAE</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid md:grid-cols-3 gap-6">
              <div>
                <h4 className="font-semibold mb-2">Visa Process</h4>
                <p className="text-sm text-gray-600">
                  Most employers provide visa sponsorship. The process typically takes 2-4 weeks.
                </p>
              </div>
              <div>
                <h4 className="font-semibold mb-2">Required Documents</h4>
                <p className="text-sm text-gray-600">
                  Passport, educational certificates, experience letters, and medical certificates.
                </p>
              </div>
              <div>
                <h4 className="font-semibold mb-2">Living in UAE</h4>
                <p className="text-sm text-gray-600">
                  Tax-free income, modern infrastructure, and multicultural environment.
                </p>
              </div>
            </div>
            <div className="mt-6">
              <Button variant="outline">
                <ExternalLink className="h-4 w-4 mr-2" />
                Complete UAE Guide
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}