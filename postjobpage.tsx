"use client"

import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { Badge } from "@/components/ui/badge"
import { MapPin, DollarSign, Clock, Users, X } from "lucide-react"
import Link from "next/link"

export default function PostJobPage() {
  const [jobType, setJobType] = useState("")
  const [skills, setSkills] = useState<string[]>([])
  const [newSkill, setNewSkill] = useState("")

  const addSkill = () => {
    if (newSkill.trim() && !skills.includes(newSkill.trim())) {
      setSkills([...skills, newSkill.trim()])
      setNewSkill("")
    }
  }

  const removeSkill = (skillToRemove: string) => {
    setSkills(skills.filter((skill) => skill !== skillToRemove))
  }

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
            <Link href="/uae-jobs" className="text-gray-600 hover:text-blue-600">
              UAE Jobs
            </Link>
            <Link href="/dashboard" className="text-gray-600 hover:text-blue-600">
              Dashboard
            </Link>
          </nav>
          <Button variant="outline">Save Draft</Button>
        </div>
      </header>

      <div className="container mx-auto px-4 py-8 max-w-4xl">
        <div className="mb-8">
          <h1 className="text-3xl font-bold mb-2">Post a Job</h1>
          <p className="text-gray-600">Find the perfect professional for your project</p>
        </div>

        <div className="grid lg:grid-cols-3 gap-8">
          {/* Main Form */}
          <div className="lg:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle>Job Details</CardTitle>
                <CardDescription>Provide clear information about your job requirements</CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                {/* Job Type Selection */}
                <div className="space-y-3">
                  <Label>Job Type</Label>
                  <div className="grid grid-cols-3 gap-4">
                    <Card
                      className={`cursor-pointer transition-colors ${jobType === "freelance" ? "ring-2 ring-blue-500 bg-blue-50" : "hover:bg-gray-50"}`}
                      onClick={() => setJobType("freelance")}
                    >
                      <CardContent className="p-4 text-center">
                        <Users className="h-8 w-8 mx-auto mb-2 text-purple-600" />
                        <h3 className="font-medium">Freelance</h3>
                        <p className="text-xs text-gray-600">Remote project work</p>
                      </CardContent>
                    </Card>
                    <Card
                      className={`cursor-pointer transition-colors ${jobType === "juakali" ? "ring-2 ring-blue-500 bg-blue-50" : "hover:bg-gray-50"}`}
                      onClick={() => setJobType("juakali")}
                    >
                      <CardContent className="p-4 text-center">
                        <MapPin className="h-8 w-8 mx-auto mb-2 text-green-600" />
                        <h3 className="font-medium">Juakali</h3>
                        <p className="text-xs text-gray-600">Local hands-on work</p>
                      </CardContent>
                    </Card>
                    <Card
                      className={`cursor-pointer transition-colors ${jobType === "uae" ? "ring-2 ring-blue-500 bg-blue-50" : "hover:bg-gray-50"}`}
                      onClick={() => setJobType("uae")}
                    >
                      <CardContent className="p-4 text-center">
                        <DollarSign className="h-8 w-8 mx-auto mb-2 text-blue-600" />
                        <h3 className="font-medium">UAE Job</h3>
                        <p className="text-xs text-gray-600">International opportunity</p>
                      </CardContent>
                    </Card>
                  </div>
                </div>

                {/* Basic Information */}
                <div className="space-y-4">
                  <div>
                    <Label htmlFor="title">Job Title</Label>
                    <Input id="title" placeholder="e.g. Senior React Developer" />
                  </div>

                  <div>
                    <Label htmlFor="description">Job Description</Label>
                    <Textarea
                      id="description"
                      placeholder="Describe what you need done, including specific requirements and expectations..."
                      rows={5}
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <Label htmlFor="category">Category</Label>
                      <Select>
                        <SelectTrigger>
                          <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="development">Development</SelectItem>
                          <SelectItem value="design">Design</SelectItem>
                          <SelectItem value="writing">Writing</SelectItem>
                          <SelectItem value="marketing">Marketing</SelectItem>
                          <SelectItem value="construction">Construction</SelectItem>
                          <SelectItem value="cleaning">Cleaning</SelectItem>
                          <SelectItem value="repair">Repair & Maintenance</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div>
                      <Label htmlFor="experience">Experience Level</Label>
                      <Select>
                        <SelectTrigger>
                          <SelectValue placeholder="Select level" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="entry">Entry Level</SelectItem>
                          <SelectItem value="intermediate">Intermediate</SelectItem>
                          <SelectItem value="expert">Expert</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                </div>

                {/* Skills */}
                <div className="space-y-3">
                  <Label>Required Skills</Label>
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
                  {skills.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                      {skills.map((skill) => (
                        <Badge key={skill} variant="secondary" className="flex items-center gap-1">
                          {skill}
                          <X className="h-3 w-3 cursor-pointer" onClick={() => removeSkill(skill)} />
                        </Badge>
                      ))}
                    </div>
                  )}
                </div>

                {/* Location & Budget */}
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="location">Location</Label>
                    <Input id="location" placeholder="e.g. Nairobi, Kenya" />
                  </div>
                  <div>
                    <Label htmlFor="budget">Budget</Label>
                    <Input id="budget" placeholder="e.g. $500 - $1000" />
                  </div>
                </div>

                {/* Timeline */}
                <div className="space-y-3">
                  <Label>Project Timeline</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="Select timeline" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="asap">ASAP (Urgent)</SelectItem>
                      <SelectItem value="week">Within a week</SelectItem>
                      <SelectItem value="month">Within a month</SelectItem>
                      <SelectItem value="flexible">Flexible</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                {/* Additional Options */}
                <div className="space-y-4">
                  <div className="flex items-center space-x-2">
                    <Checkbox id="featured" />
                    <Label htmlFor="featured">Make this a featured job (+$10)</Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Checkbox id="urgent" />
                    <Label htmlFor="urgent">Mark as urgent (+$5)</Label>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Checkbox id="verified" />
                    <Label htmlFor="verified">Only verified professionals</Label>
                  </div>
                </div>

                {/* Submit Buttons */}
                <div className="flex gap-4 pt-6">
                  <Button className="flex-1">Post Job</Button>
                  <Button variant="outline">Save as Draft</Button>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Pricing */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Pricing</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between">
                  <span>Job Posting</span>
                  <span>Free</span>
                </div>
                <div className="flex justify-between text-gray-600">
                  <span>Featured (+optional)</span>
                  <span>$10</span>
                </div>
                <div className="flex justify-between text-gray-600">
                  <span>Urgent (+optional)</span>
                  <span>$5</span>
                </div>
                <hr />
                <div className="flex justify-between font-medium">
                  <span>Total</span>
                  <span>$0</span>
                </div>
              </CardContent>
            </Card>

            {/* Tips */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Tips for Success</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3 text-sm">
                <div className="flex gap-2">
                  <Clock className="h-4 w-4 text-blue-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="font-medium">Be specific</p>
                    <p className="text-gray-600">Clear requirements get better applications</p>
                  </div>
                </div>
                <div className="flex gap-2">
                  <DollarSign className="h-4 w-4 text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="font-medium">Fair budget</p>
                    <p className="text-gray-600">Competitive rates attract top talent</p>
                  </div>
                </div>
                <div className="flex gap-2">
                  <Users className="h-4 w-4 text-purple-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="font-medium">Respond quickly</p>
                    <p className="text-gray-600">Fast responses improve your success rate</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Support */}
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Need Help?</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-gray-600 mb-4">
                  Our team is here to help you create the perfect job posting.
                </p>
                <Button variant="outline" className="w-full">
                  Contact Support
                </Button>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  )
}