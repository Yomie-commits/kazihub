"use client"

import type React from "react"
import { createContext, useContext, useState, useEffect } from "react"

interface User {
  id: string
  email: string
  name: string
  userType: "freelancer" | "client" | "job_seeker"
  avatar?: string
  profileComplete: boolean
  verified: boolean
  location?: string
  phone?: string
  bio?: string
  skills?: string[]
  experience?: string
  hourlyRate?: string
  portfolio?: string[]
  companyName?: string
  companySize?: string
  industry?: string
}

interface AuthContextType {
  user: User | null
  login: (email: string, password: string) => Promise<boolean>
  register: (userData: RegisterData) => Promise<boolean>
  logout: () => void
  updateProfile: (data: Partial<User>) => Promise<boolean>
  isLoading: boolean
}

interface RegisterData {
  email: string
  password: string
  name: string
  userType: "freelancer" | "client" | "job_seeker"
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    // Check for existing session
    const savedUser = localStorage.getItem("kazihub_user")
    if (savedUser) {
      setUser(JSON.parse(savedUser))
    }
    setIsLoading(false)
  }, [])

  const login = async (email: string, password: string): Promise<boolean> => {
    setIsLoading(true)

    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000))

    // Mock authentication - in real app, this would be an API call
    if (email && password) {
      const mockUser: User = {
        id: "1",
        email,
        name: email.split("@")[0],
        userType: "freelancer",
        profileComplete: false,
        verified: false,
      }

      setUser(mockUser)
      localStorage.setItem("kazihub_user", JSON.stringify(mockUser))
      setIsLoading(false)
      return true
    }

    setIsLoading(false)
    return false
  }

  const register = async (userData: RegisterData): Promise<boolean> => {
    setIsLoading(true)

    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 1000))

    const newUser: User = {
      id: Date.now().toString(),
      email: userData.email,
      name: userData.name,
      userType: userData.userType,
      profileComplete: false,
      verified: false,
    }

    setUser(newUser)
    localStorage.setItem("kazihub_user", JSON.stringify(newUser))
    setIsLoading(false)
    return true
  }

  const logout = () => {
    setUser(null)
    localStorage.removeItem("kazihub_user")
  }

  const updateProfile = async (data: Partial<User>): Promise<boolean> => {
    if (!user) return false

    setIsLoading(true)

    // Simulate API call
    await new Promise((resolve) => setTimeout(resolve, 500))

    const updatedUser = { ...user, ...data }

    // Calculate profile completion
    const requiredFields = ["name", "email", "location", "phone", "bio"]
    const userTypeFields = {
      freelancer: ["skills", "experience", "hourlyRate"],
      client: ["companyName", "industry"],
      job_seeker: ["skills", "experience"],
    }

    const allRequiredFields = [...requiredFields, ...userTypeFields[updatedUser.userType]]
    const completedFields = allRequiredFields.filter((field) => updatedUser[field as keyof User])
    const completionPercentage = (completedFields.length / allRequiredFields.length) * 100

    updatedUser.profileComplete = completionPercentage >= 80

    setUser(updatedUser)
    localStorage.setItem("kazihub_user", JSON.stringify(updatedUser))
    setIsLoading(false)
    return true
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        login,
        register,
        logout,
        updateProfile,
        isLoading,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error("useAuth must be used within an AuthProvider")
  }
  return context
}