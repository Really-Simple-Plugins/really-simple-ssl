import * as React from "react";
const SvgGlGreenland = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="GL_-_Greenland_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#GL_-_Greenland_svg__a)">
      <path
        fill="#C51918"
        fillRule="evenodd"
        d="M0 6h16v6H0V6Z"
        clipRule="evenodd"
      />
      <mask
        id="GL_-_Greenland_svg__b"
        width={16}
        height={6}
        x={0}
        y={6}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 6h16v6H0V6Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#GL_-_Greenland_svg__b)">
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="M6 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
          clipRule="evenodd"
        />
      </g>
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v6H0V0Z"
        clipRule="evenodd"
      />
      <mask
        id="GL_-_Greenland_svg__c"
        width={16}
        height={6}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0h16v6H0V0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#GL_-_Greenland_svg__c)">
        <path
          fill="#C51918"
          fillRule="evenodd"
          d="M6 10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgGlGreenland;
